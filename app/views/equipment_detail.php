<?php
function equipmentDetailEscape($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
$statusLabels = ['available'=>'Sẵn sàng','maintenance'=>'Đang bảo trì','broken'=>'Bị hỏng','retired'=>'Ngừng sử dụng'];
$qrUrl = 'index.php?action=equipment_detail&id=' . (int)$equipment['equipment_id'];
?>
<!DOCTYPE html>
<html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Chi tiết thiết bị - Smart Campus</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body><div class="container" style="max-width:900px;margin:35px auto;padding:24px;background:#fff;border-radius:14px">
<h2>Chi tiết thiết bị</h2>
<p><strong>Mã thiết bị:</strong> <?= equipmentDetailEscape($equipment['equipment_code']) ?></p>
<p><strong>Tên thiết bị:</strong> <?= equipmentDetailEscape($equipment['equipment_name']) ?></p>
<p><strong>Phòng:</strong> <?= equipmentDetailEscape(($equipment['room_code'] ?? '') . ' - ' . ($equipment['room_name'] ?? '')) ?></p>
<p><strong>Loại:</strong> <?= equipmentDetailEscape($equipment['type_name'] ?? '') ?></p>
<p><strong>Hãng:</strong> <?= equipmentDetailEscape($equipment['brand'] ?? '') ?></p>
<p><strong>Trạng thái:</strong> <?= equipmentDetailEscape($statusLabels[($equipment['availability_status'] ?? $equipment['status'])] ?? ($equipment['availability_status'] ?? $equipment['status'])) ?></p>
<p><strong>Mô tả:</strong> <?= nl2br(equipmentDetailEscape($equipment['description'] ?? '')) ?></p>
<hr><h3>QR định danh thiết bị</h3><p>Quét mã để mở trang chi tiết này. QR không cấp quyền chỉnh sửa dữ liệu.</p>
<div id="equipment-qr" style="margin:16px 0"></div>
<button type="button" onclick="window.print()">In QR / trang</button>
<p><a href="index.php?action=equipment">← Quay lại danh sách thiết bị</a></p>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function(){
 const target = document.getElementById('equipment-qr');
 const url = new URL(<?= json_encode($qrUrl, JSON_UNESCAPED_SLASHES) ?>, window.location.href).href;
 if (window.QRCode) new QRCode(target, {text:url,width:190,height:190,correctLevel:QRCode.CorrectLevel.M});
 else target.textContent = 'Không tải được thư viện QR. Vui lòng kiểm tra kết nối Internet.';
})();
</script></body></html>
