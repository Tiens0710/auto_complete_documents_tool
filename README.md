# AIVANBAN

Công cụ web nhập dữ liệu Excel và chuẩn bị bộ hồ sơ đào tạo nghề theo nguyên tắc:

`bộ hồ sơ cơ bản (Mẫu 1-7) + bộ hồ sơ riêng theo trình độ`.

## Trạng thái hiện tại

Phiên bản nền tảng đã có:

- Chọn sơ cấp, trung cấp hoặc cao đẳng.
- Xác định đúng bộ biểu mẫu theo trình độ.
- Tải và đọc `.xlsx`, `.xls`, `.csv` tối đa 10 MB.
- Tự nhận diện sheet `SinhVien` và đề xuất ánh xạ cột.
- Cho phép sửa ánh xạ cột.
- Kiểm tra trường bắt buộc và mã người học trùng.
- Xem trước tối đa 20 dòng trên giao diện.
- Kiểm tra sheet dữ liệu còn thiếu cho từng biểu mẫu.
- Tải workbook mẫu gồm 10 sheet nghiệp vụ.
- Chuyển ngày dạng số nội bộ của Excel về định dạng `dd/mm/yyyy` khi xem trước.
- Xuất một bộ PDF sạch theo từng biểu mẫu, kèm README trong file ZIP.
- Ô ảnh hồ sơ dùng kích thước 30×40 mm theo tỉ lệ ảnh 3×4.
- Các PDF đều được dựng trên khổ A4; dữ liệu thiếu được để trống, không tự bịa nội dung.
- Bản PDF được dựng theo ảnh tham chiếu; trước khi ký, cấp bằng/chứng chỉ hoặc nộp hồ sơ cần được cơ sở nghiệm thu.

Chưa có database, đăng nhập và bộ mẫu in chính thức của cơ sở. Muốn phát hành bản chính thức phải có file mẫu trống của trường và nghiệm thu từng mẫu. Xem `docs/FORM_FORMAT_AUDIT.md`.

## Chạy tại máy phát triển

Yêu cầu PHP 8.2 trở lên với các extension: `fileinfo`, `mbstring`, `xml`, `xmlreader`, `xmlwriter`, `zip`, `gd`. Thư viện mPDF được cài qua Composer.

```bash
composer install
php -S 127.0.0.1:8080 -t public
```

Mở `http://127.0.0.1:8080`.

Chạy kiểm thử:

```bash
php tests/run.php
```

## Triển khai bằng subdomain trên cPanel

1. Tạo subdomain, ví dụ `vanban.tenmien.vn`.
2. Đặt Document Root của subdomain trỏ đến thư mục `AIVANBAN/public`.
3. Chọn PHP 8.3 và bật các extension được liệt kê ở trên.
4. Chạy `composer install --no-dev --optimize-autoloader` bằng Terminal của cPanel. Nếu hosting không có Composer, chạy ở máy local rồi tải cả thư mục `vendor` lên.
5. Đảm bảo PHP có quyền ghi vào `storage/uploads`, `storage/logs` và `storage/sessions` nhưng các thư mục này không được đặt trong web root.
6. Bật HTTPS cho subdomain trước khi nhập dữ liệu thật.

Không trỏ Document Root vào toàn bộ thư mục `AIVANBAN`, vì như vậy tài liệu nghiên cứu và file cấu hình có thể bị truy cập trực tiếp.

## Tài liệu dự án

- `AGENTS.md`: ý định và nguyên tắc phát triển dài hạn.
- `docs/FORM_RESEARCH.md`: căn cứ pháp lý và bản đồ trường của các biểu mẫu.
