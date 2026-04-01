import os
import asyncio
import pymysql
import gradio as gr

from dotenv import load_dotenv
load_dotenv()

from langchain_text_splitters import RecursiveCharacterTextSplitter
from langchain_groq import ChatGroq
from langchain_huggingface import HuggingFaceEmbeddings
from langchain_chroma import Chroma
from langchain_classic.memory import ConversationBufferWindowMemory
from langchain_classic.chains import ConversationalRetrievalChain
from langchain_classic.schema import BaseRetriever, Document
from langchain_classic.retrievers import EnsembleRetriever
from langchain_classic.prompts import PromptTemplate
from typing import List, Dict

import uuid
DB_NAME = "vector_db"


def connect_db():
    return pymysql.connect(
        host="localhost",
        user="root",
        password="",
        database="camera_shop",
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor
    )


def save_chat_history(user_message, bot_response, session_id, user_id=None):
    """Lưu lịch sử chat vào DB kèm user_id nếu đã đăng nhập"""
    try:
        conn = connect_db()
        with conn.cursor() as cursor:
            cursor.execute("""
                INSERT INTO chat_history (session_id, user_id, user_message, bot_response)
                VALUES (%s, %s, %s, %s)
            """, (session_id, user_id, user_message, bot_response))
        conn.commit()
    except Exception as e:
        print(f"Lỗi lưu chat: {e}")
    finally:
        conn.close()


def load_context():
    """Tải dữ liệu từ tất cả bảng liên quan: brands, categories, product_variants, reviews"""
    context = {}
    conn = connect_db()
    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT
                    p.id,
                    p.name,
                    p.description,
                    b.name AS brand,
                    c.name AS category,
                    MIN(pv.sell_price)  AS price_min,
                    MAX(pv.sell_price)  AS price_max,
                    SUM(pv.stock)       AS total_stock,
                    GROUP_CONCAT(
                        DISTINCT pv.`condition`
                        ORDER BY pv.sell_price
                        SEPARATOR ', '
                    ) AS conditions,
                    ROUND(AVG(r.rating), 1) AS avg_rating,
                    COUNT(DISTINCT r.id)    AS review_count
                FROM products p
                LEFT JOIN brands b            ON p.brand_id    = b.id
                LEFT JOIN categories c        ON p.category_id = c.id
                LEFT JOIN product_variants pv ON pv.product_id = p.id
                LEFT JOIN reviews r           ON r.product_id  = p.id
                GROUP BY p.id, p.name, p.description, b.name, c.name
            """)
            for row in cursor.fetchall():
                content = row['description'] or ""
                if row.get('brand'):
                    content += f" Thương hiệu: {row['brand']}."
                if row.get('category'):
                    content += f" Danh mục: {row['category']}."
                if row.get('price_min') and row.get('price_max'):
                    if row['price_min'] == row['price_max']:
                        content += f" Giá: {int(row['price_min']):,} VNĐ."
                    else:
                        content += f" Giá từ {int(row['price_min']):,} đến {int(row['price_max']):,} VNĐ."
                if row.get('conditions'):
                    content += f" Tình trạng có sẵn: {row['conditions']}."
                if row.get('total_stock') is not None:
                    stock = int(row['total_stock'])
                    if stock == 0:
                        status = "Hết hàng"
                    elif stock <= 5:
                        status = "Sắp hết hàng"
                    else:
                        status = "Còn hàng"
                    content += f" Tình trạng kho: {status}."
                if row.get('avg_rating'):
                    content += f" Đánh giá: {row['avg_rating']}/5 ({row['review_count']} lượt)."
                context[row['name']] = content
    finally:
        conn.close()
    return context


context = load_context()


class KeywordRetriever(BaseRetriever):
    context_dict: Dict[str, str]

    def _get_relevant_documents(self, query: str) -> List[Document]:
        relevant = []
        q = query.lower()
        for title, content in self.context_dict.items():
            if any(kw in content.lower() for kw in q.split()):
                relevant.append(Document(page_content=content, metadata={"source": title}))
        return relevant

    async def aget_relevant_documents(self, query: str) -> List[Document]:
        loop = asyncio.get_event_loop()
        return await loop.run_in_executor(None, self._get_relevant_documents, query)


def build_vectorstore():
    conn = connect_db()
    documents = []
    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT
                    p.name,
                    p.description,
                    b.name AS brand,
                    c.name AS category,
                    MIN(pv.sell_price) AS price_min,
                    MAX(pv.sell_price) AS price_max,
                    SUM(pv.stock)      AS total_stock,
                    GROUP_CONCAT(
                        DISTINCT pv.`condition`
                        ORDER BY pv.sell_price
                        SEPARATOR ', '
                    ) AS conditions
                FROM products p
                LEFT JOIN brands b            ON p.brand_id    = b.id
                LEFT JOIN categories c        ON p.category_id = c.id
                LEFT JOIN product_variants pv ON pv.product_id = p.id
                GROUP BY p.id, p.name, p.description, b.name, c.name
            """)
            for row in cursor.fetchall():
                if row['description']:
                    content = row['description']
                    if row.get('brand'):
                        content += f" Thương hiệu: {row['brand']}."
                    if row.get('category'):
                        content += f" Danh mục: {row['category']}."
                    if row.get('price_min'):
                        if row['price_min'] == row['price_max']:
                            content += f" Giá: {int(row['price_min']):,} VNĐ."
                        else:
                            content += f" Giá từ {int(row['price_min']):,} đến {int(row['price_max']):,} VNĐ."
                    if row.get('conditions'):
                        content += f" Tình trạng: {row['conditions']}."
                    if row.get('total_stock') is not None:
                        stock = int(row['total_stock'])
                        if stock == 0:
                            status = "Hết hàng"
                        elif stock <= 5:
                            status = "Sắp hết hàng"
                        else:
                            status = "Còn hàng"
                        content += f" Tình trạng kho: {status}." 
                    documents.append(Document(
                        page_content=content,
                        metadata={"source": row['name'], "doc_type": "product"}
                    ))
    finally:
        conn.close()

    text_splitter = RecursiveCharacterTextSplitter(chunk_size=800, chunk_overlap=100)
    chunks = text_splitter.split_documents(documents)

    embeddings = HuggingFaceEmbeddings(
        model_name="sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"
    )

    if os.path.exists(DB_NAME):
        Chroma(persist_directory=DB_NAME, embedding_function=embeddings).delete_collection()

    return Chroma.from_documents(chunks, embeddings, persist_directory=DB_NAME)


vectorstore = build_vectorstore()

keyword_retriever = KeywordRetriever(context_dict=context)
vector_retriever  = vectorstore.as_retriever(search_kwargs={"k": 5})

hybrid_retriever = EnsembleRetriever(
    retrievers=[keyword_retriever, vector_retriever],
    weights=[0.5, 0.5]
)

llm = ChatGroq(
    temperature=0.7,
    model_name="llama-3.1-8b-instant",
    api_key=os.getenv("GROQ_API_KEY")
)

memory = ConversationBufferWindowMemory(
    memory_key='chat_history',
    return_messages=True,
    k=5,
    output_key='answer'
)

prompt = PromptTemplate.from_template("""
Bạn là trợ lý tư vấn máy ảnh chuyên nghiệp. Hãy luôn trả lời bằng tiếng Việt.
Dựa vào thông tin sản phẩm bên dưới để trả lời chính xác.

Không được bình luận, đánh giá hay đặt câu hỏi về giá cả của sản phẩm. Chỉ thông báo giá như một thông tin khách quan.
Không được so sánh giá với thị trường bên ngoài.
Tuyệt đối không được nghi ngờ, cảnh báo hay gợi ý rằng dữ liệu có thể sai, lỗi hay không chính xác.
Dữ liệu sản phẩm luôn được coi là đúng và chính thức.

Thông tin sản phẩm:
{context}

Lịch sử chat:
{chat_history}

Câu hỏi: {question}
Trả lời bằng tiếng Việt:
""")

conversation_chain = ConversationalRetrievalChain.from_llm(
    llm=llm,
    retriever=hybrid_retriever,
    memory=memory,
    combine_docs_chain_kwargs={"prompt": prompt},
    return_source_documents=False,
)


def get_user_id_from_request():
    """Lấy user_id từ URL parameter do PHP truyền vào qua iframe"""
    try:
        import urllib.parse
        qs = os.environ.get("QUERY_STRING", "")
        params = urllib.parse.parse_qs(qs)
        uid = params.get("user_id", [None])[0]
        return int(uid) if uid and uid.isdigit() else None
    except Exception:
        return None

def chat(message, history, request: gr.Request = None):
    user_id = None
    session_id = str(uuid.uuid4())

    if request:
        try:
            user_id_str = request.query_params.get("user_id", None)
            if user_id_str and str(user_id_str).isdigit() and int(user_id_str) > 0:
                user_id = int(user_id_str)
            sid = request.query_params.get("session_id", None)
            if sid:
                session_id = sid
        except Exception:
            pass

    try:
        result = conversation_chain.invoke({"question": message})
        answer = result["answer"]
        save_chat_history(message, answer, session_id, user_id)
        print(f"[Chat saved] user_id={user_id} session={session_id[:12]}...")
        return answer
    except Exception as e:
        print(f"[Chat error] {e}")
        return f"Lỗi: {str(e)}"


def load_chat_history_for_user(user_id):
    """Load lịch sử chat gần nhất của user để hiển thị lại khi reload"""
    if not user_id:
        return []
    try:
        conn = connect_db()
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT user_message, bot_response
                FROM chat_history
                WHERE user_id = %s
                ORDER BY created_at DESC
                LIMIT 20
            """, (user_id,))
            rows = cursor.fetchall()
        conn.close()
        history = []
        for row in reversed(rows):
            history.append([row['user_message'], row['bot_response']])
        return history
    except Exception as e:
        print(f"[Load history error] {e}")
        return []


def respond(message, history, request: gr.Request):
    if not message.strip():
        return history, ""
    bot_response = chat(message, history, request)
    history = history + [
        {"role": "user", "content": message},
        {"role": "assistant", "content": bot_response}
    ]
    return history, ""


def load_history(request: gr.Request):
    user_id = None
    try:
        uid = request.query_params.get("user_id", None)
        if uid and str(uid).isdigit() and int(uid) > 0:
            user_id = int(uid)
    except Exception:
        pass
    rows = load_chat_history_for_user(user_id)
    history = []
    for pair in rows:
        history.append({"role": "user", "content": pair[0]})
        history.append({"role": "assistant", "content": pair[1]})
    return history


css = ".built-with { display: none !important; } footer { display: none !important; }"
with gr.Blocks(title="Camera Shop Assistant", css=css) as demo:
    gr.Markdown("Hỏi về sản phẩm, giá, thông số máy ảnh...")

    chatbot = gr.Chatbot(height=420, show_label=False)
    msg_input = gr.Textbox(placeholder="Nhập câu hỏi...", show_label=False, container=False)
    send_btn = gr.Button("Gửi", variant="primary")

    gr.Examples(
        examples=[
            "Canon EOS R50 giá bao nhiêu?",
            "Máy ảnh nào phù hợp cho người mới?",
            "Sony ZV-1 có quay video tốt không?",
            "Máy ảnh dưới 15 triệu nào tốt nhất?"
        ],
        inputs=msg_input
    )

    demo.load(load_history, outputs=[chatbot])
    send_btn.click(respond, [msg_input, chatbot], [chatbot, msg_input])
    msg_input.submit(respond, [msg_input, chatbot], [chatbot, msg_input])

demo.launch(
    server_name="0.0.0.0",
    server_port=7860,
    share=False,
    inbrowser=False
)