<?php
require_once "../../includes/admin_auth.php";
require_once "../../models/Brand.php";
require_once "../../models/Category.php";
require_once "../../config/database.php";

$brands = (new Brand($pdo))->all();
$categories = (new Category($pdo))->all();
?>

<form action="store.php" method="post" enctype="multipart/form-data">
<h3>Thêm sản phẩm</h3>

<input name="name" placeholder="Tên sản phẩm" required><br>

<select name="brand_id">
<?php foreach ($brands as $b): ?>
<option value="<?= $b['id'] ?>"><?= $b['name'] ?></option>
<?php endforeach ?>
</select>

<select name="category_id">
<?php foreach ($categories as $c): ?>
<option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
<?php endforeach ?>
</select>

<textarea name="description"></textarea>

<h4>Biến thể</h4>
<input name="condition[]" placeholder="Mới / Cũ 95%">
<input name="cost_price[]" placeholder="Giá gốc">
<input name="sell_price[]" placeholder="Giá bán">
<input name="stock[]" placeholder="Tồn kho">

<br><br>
<input type="file" name="images[]" multiple>

<br><br>
<button>Thêm</button>
</form>
