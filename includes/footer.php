<footer>

<?php
$chat_user_id = $_SESSION['user']['id'] ?? 0;
$chat_user_name = $_SESSION['user']['name'] ?? 'Khách';
?>
<script>
window.CHAT_USER_ID = <?= $chat_user_id ?>;
window.CHAT_USER_NAME = "<?= htmlspecialchars($chat_user_name) ?>";
</script>
<script src="/camerashop/public/chat_widget.js"></script>

<?php include __DIR__ . '/../chat_AI/chat_widget.php'; ?>



    <ul class="footer-top">
        <li><a href="#">Quan tâm</a></li>
        <li><a href="#">Liên hệ</a></li>
        <li><a href="#">Tuyển dụng</a></li>
        <li><a href="#">Giới thiệu</a></li>
        <li class="social">
            <a href="https://www.facebook.com/share/178YNvnDon/?mibextid=wwXIfr"><i class="fab fa-facebook-f"></i></a>
            <a href="https://www.facebook.com/share/178YNvnDon/?mibextid=wwXIfr"><i class="fab fa-telegram"></i></a>
            <a href="https://www.youtube.com/@duongminhnhut-2005?si=oIiO2wH9s7MGG4TI"><i class="fab fa-youtube"></i></a>
        </li>
    </ul>

    
    <p>
        Công ty TNHH DMN-clothing<br>
        Địa chỉ đăng ký: xã Hòa An, huyện Phụng Hiệp, tỉnh Hậu Giang<br>
        Email: nhutb2306635@student.ctu.edu.vn<br>
        Liên hệ (Nhựt): <strong>0942.726.775</strong>
    </p>
    <div class="footer-bottom">© c</div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>