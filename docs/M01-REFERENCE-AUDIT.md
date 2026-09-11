# M01 — đối chiếu ảnh ngày 10/09/2026

Nguồn: hai ảnh người dùng gửi, ảnh mẫu chụp xoay và ảnh PDF sai. Phạm vi lần sửa này là M01, không phải xác nhận toàn bộ 18 mẫu.

## Bố cục đã sửa

- A4 ngang 297 × 210 mm theo yêu cầu người dùng; ảnh nguồn ghi A2. Đây là khác biệt có chủ ý, không phải bản sao nguyên kích thước.
- Dựng đường bảng theo mm, không dùng bảng HTML tự co cột.
- Ô TT, ô LỚP chia ba đường chéo; ba hàng Tháng / Tuần / Từ ngày đến ngày; Ghi chú cuối bảng.
- Mỗi dòng là một lớp; các lịch cùng lớp/cùng tuần được giữ đầy đủ.
- Chú giải hai hàng, bốn cột, ô chữ nhật; ghi chú theo ảnh; hiệu trưởng bên trái, trưởng phòng đào tạo bên phải.
- Chữ serif đen, nền trắng; bỏ watermark lớn và dòng chân trang thử nghiệm để trang in sạch.

## Điều chỉnh để chứa dữ liệu thực

- Luôn có tuần 1–6 và 47–52; chỉ rút gọn khoảng tuần trống bằng dấu … như ảnh. Bất kỳ tuần giữa năm có dữ liệu đều được hiển thị. Tối đa 18 cột thời gian và 5 lớp mỗi trang; vượt thì nối trang, không bỏ dữ liệu.
- E1, E2… là mã tham chiếu dòng dữ liệu đến phụ lục, không phải ký hiệu hoạt động đã được nhà trường phê duyệt. Chưa tự đặt ký hiệu cho tám ô chú giải khi trường chưa cung cấp quy ước.
- Phụ lục giữ toàn bộ nội dung, ngày và ghi chú gốc. Cùng tuần có nhiều khoảng ngày thì đầu bảng ghi xem chi tiết.
- Không suy diễn năm học từ mã khóa. Thiếu năm học thì để dòng chấm.
- Chặn lịch thiếu mã lớp hoặc tuần không nguyên / ngoài 1–52.

## Kiểm chứng

Chạy `tests/progress.php` và `tests/run.php`; xuất mẫu Excel 5 lịch thành 1 dòng lớp, 2 trang PDF (bảng + chi tiết). Xác nhận PDF A4 ngang bằng pdfinfo và xem ảnh render cả hai trang. Có test hai lớp, nhiều lịch cùng tuần, tuần 26 và tuần không hợp lệ.

Chưa có căn cứ để tuyên bố giống ảnh 100% hay được phê duyệt sử dụng chính thức. Cần xác nhận quy ước ký hiệu của trường trước khi coi là bản hoàn tất nghiệp vụ.
