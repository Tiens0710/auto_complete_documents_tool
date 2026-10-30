# AIVANBAN

Công cụ web nhập dữ liệu Excel và chuẩn bị bộ hồ sơ đào tạo nghề theo nguyên tắc:

`bộ hồ sơ cơ bản (Mẫu 1-7) + bộ hồ sơ riêng theo trình độ`.

## Trạng thái hiện tại

Phiên bản nền tảng đã có:

- Chọn sơ cấp, trung cấp hoặc cao đẳng.
- Giao diện chia thành ba trang: tải Excel → kiểm tra danh sách → xuất hồ sơ. Mẫu trống mở ở trang riêng; các bước sau chỉ mở khi dữ liệu đã hợp lệ.
- Xác định đúng bộ biểu mẫu theo trình độ.
- Tải và đọc `.xlsx` tối đa 10 MB.
- Tự nhận diện dữ liệu một sheet theo mẫu người học; ghép `Họ đệm` + `Tên` và dùng `TT` làm mã liên kết.
- Lớp, khóa và nghề/ngành không có trong mẫu người học được để trống, không chặn nhập.
- Kiểm tra trường bắt buộc và mã người học trùng.
- Xem trước tối đa 20 dòng trên giao diện.
- Kiểm tra sheet dữ liệu còn thiếu cho từng biểu mẫu.
- Tải workbook mẫu một sheet `SinhVien`, đúng 22 cột dữ liệu người học.
- Thông tin văn bằng có thể điền vào biểu mẫu cấp bằng; điểm tổng hợp không được suy diễn thành bảng điểm theo mô-đun.
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

## Cổng tra cứu văn bằng bằng QR

Phần thử nghiệm nằm ở `public/chungchi` và dùng dữ liệu người học trong file `.xlsx`. PDF hiện tạo là **bản xem thử có watermark, chưa có giá trị cấp phát**. Cần nhận mẫu văn bằng trống đã được trường duyệt và nghiệm thu bố cục trước khi dùng để cấp văn bằng.

Để chạy cổng trên cPanel:

1. Tạo subdomain `chungchi.mtpc.edu.vn`, bật HTTPS và đặt Document Root thành `/home/mtpc/public_html/auto_complete_documents_tool/public/chungchi`.
2. Bật PHP 8.3 cùng các extension `fileinfo`, `mbstring`, `zip` và `gd`. Đặt giới hạn upload và POST tối thiểu 10 MB.
3. Tạo MySQL database/user trong cPanel. Dùng phpMyAdmin nhập `database/chungchi.sql`.
4. Trong File Manager, sao chép `storage/chungchi-config.example.php` thành `storage/chungchi-config.php`, rồi điền thông tin database và mật khẩu quản trị dài, riêng biệt.
5. Kiểm tra `storage/sessions`, `storage/credentials` và `storage/tmp/chungchi` có quyền ghi cho PHP. Các PDF nằm ngoài thư mục public.
6. Cập nhật thư mục `vendor` trên hosting theo `composer.lock`. Nếu cPanel không có Terminal, có thể tạo gói ZIP thư mục `vendor` ở máy phát triển rồi tải và giải nén vào thư mục gốc của ứng dụng bằng File Manager; Git deployment chỉ chép `vendor` nếu thư mục đó đã có sẵn trong repository cPanel. Không thay `vendor` bằng phiên bản cũ.
7. Trang cán bộ: `https://chungchi.mtpc.edu.vn/admin.php`. Cán bộ tải Excel, xem lại danh sách, rồi xác nhận tạo PDF nháp; QR đưa người xem đến trang tra cứu và PDF tương ứng.

Mỗi lần tạo giới hạn 30 người, chỉ nhận `.xlsx`, và số hiệu văn bằng trùng trong file hoặc database sẽ bị từ chối. Bản xem trước dữ liệu tạm nằm trong phiên đăng nhập tối đa 15 phút; file Excel nguồn không được lưu lại.

## Tài liệu dự án

- `AGENTS.md`: ý định và nguyên tắc phát triển dài hạn.
- `docs/FORM_RESEARCH.md`: căn cứ pháp lý và bản đồ trường của các biểu mẫu.
