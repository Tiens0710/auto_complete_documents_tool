# Kiểm tra lại định dạng biểu mẫu AIVANBAN

Ngày kiểm tra: 09/09/2026

## Kết luận bắt buộc

Bộ PDF do AIVANBAN tạo là bản điền dữ liệu sạch để kiểm tra/in theo quy trình của cơ sở; trước khi ký, nộp, cấp bằng/chứng chỉ hoặc lưu chính thức vẫn phải được người có thẩm quyền kiểm tra.

Sai sót gốc là hệ thống đã lấy danh sách trường dữ liệu rồi tự dựng tất cả tài liệu về A4. Cách làm này làm mất bìa sổ, trang chứng nhận, mục lục, nhiều trang con, đúng khổ giấy, đúng thứ tự cột và các phần hướng dẫn sử dụng.

## Cập nhật sau khi có bộ ảnh chụp biểu mẫu

Lớp xuất hiện tại là bộ dựng PDF sạch: tách từng nhóm biểu mẫu, dùng khổ A4 theo yêu cầu, có trang con, bảng dữ liệu và bố cục theo ảnh. Mẫu 1-2 đã có lưới tuần nhiều cột; Mẫu 3-7 đã có bìa và các trang nội dung riêng; Mẫu 12 đã có 4 nhóm trang gồm thuyết minh, hướng dẫn, lịch 52 tuần/phân bổ và thi tốt nghiệp. Mẫu 9, 10, 13, 14, 17, 18 đã có bìa, trang chứng nhận và bảng sổ theo cấu trúc ảnh; Mẫu 11, 15, 16 đã có bìa, chứng nhận, lý lịch, kết quả học tập và tốt nghiệp theo bộ ảnh. Chức năng này phục vụ luồng dữ liệu và bố cục trên máy local/cPanel; trước khi ký hoặc sử dụng chính thức vẫn phải đối chiếu mẫu trống và nghiệm thu tại cơ sở.

Theo xác nhận của người dùng ngày 10/09/2026, chế độ xuất thử nghiệm hiện quy đổi toàn bộ tài liệu về giấy A4: Mẫu 1, 2, 8 và 12 dùng A4 ngang; các mẫu còn lại dùng A4 dọc. Cột “Khổ ghi trên mẫu” trong bảng bên dưới vẫn giữ khổ của tài liệu legacy để đối chiếu nguồn, không phải kích thước file PDF thử nghiệm hiện tại.

## Nguồn dùng để đối chiếu

1. File Word gốc `QD-62-2008-BLDTBXH-original.doc`, tải từ Cổng Thông tin điện tử Chính phủ.
2. Bản PDF chuyển đổi cục bộ `QD-62-2008-BLDTBXH-original.pdf`, 73 trang, dùng để xem bố cục. File gốc sử dụng bảng mã/phông TCVN3 nên chữ có thể hiển thị sai nếu máy không có bộ phông `.VnTime`; điều này không làm thay đổi cấu trúc bảng và trang.
3. Bản Công báo `TT-23-2018-BLDTBXH.pdf`, 6 trang, dùng để xác định quy định hiện hành với trung cấp và cao đẳng.

## Tình trạng pháp lý cần thể hiện trong phần mềm

- Quyết định 62/2008/QĐ-BLĐTBXH ban hành bộ Mẫu 1-18 nhưng đã bị Thông tư 23/2018/TT-BLĐTBXH bãi bỏ đối với đào tạo trung cấp và cao đẳng từ ngày 21/01/2019.
- Với trung cấp và cao đẳng hiện nay, Thông tư 23/2018 quy định nội dung tối thiểu; hiệu trưởng từng trường quy định biểu mẫu cụ thể.
- Vì vậy phần mềm không được gọi bộ Mẫu 1-18 là "mẫu chính thức hiện hành" nếu chưa có quyết định/mẫu được trường phê duyệt.
- Chỉ được dùng bố cục Quyết định 62/2008 ở chế độ `legacy`, cho khóa thuộc phạm vi chuyển tiếp hoặc khi đơn vị xác nhận rõ đang sử dụng lại bố cục này.

## Bản đồ trang của bộ mẫu legacy

| Mẫu | Nội dung | Khổ ghi trên mẫu | Trang trong PDF đối chiếu | Thành phần bản thử nghiệm bỏ sót |
|---:|---|---|---:|---|
| 1 | Tiến độ đào tạo | A2 | 3 | Đã dựng lưới 52 tuần, lớp/tháng/tuần, chú giải hoạt động và ký |
| 2 | Kế hoạch giáo viên | A2 | 4 | Đã dựng bảng nhóm bố trí giảng dạy, tuần 1-26, nhiệm vụ khác và so sánh giờ |
| 3 | Sổ lên lớp | 26 x 38,5 cm | 5-15 | Đã dựng bìa, mục lục, giáo viên, thời khóa biểu, ngày học, nội dung, điểm, tổng hợp và hướng dẫn |
| 4 | Sổ tay giáo viên | 19 x 26,5 cm | 16-24 | Đã dựng bìa, thông tin môn/lớp, danh sách, điểm, chuyên cần và nhận xét |
| 5 | Giáo án lý thuyết | 19 x 26,5 cm | 25-27 | Đã dựng bìa và trang giáo án lý thuyết riêng |
| 6 | Giáo án thực hành | 19 x 26,5 cm | 28-31 | Đã dựng bìa và trang giáo án thực hành riêng |
| 7 | Giáo án tích hợp | 19 x 26,5 cm | 32-34 | Đã dựng bìa và trang giáo án tích hợp riêng |
| 8 | Kế hoạch đào tạo sơ cấp | 29,5 x 42 cm | 35-38 | 10 nhóm thông tin, bảng phân bổ và hướng dẫn sử dụng |
| 9 | Sổ cấp chứng chỉ sơ cấp | 24 x 32 cm | 39-41 | Cần đối chiếu lại tên cột với mẫu trống của cơ sở |
| 10 | Sổ cấp bản sao chứng chỉ | 24 x 32 cm | 42-44 | Cần đối chiếu lại tên cột với mẫu trống của cơ sở |
| 11 | Sổ quản lý học sinh sơ cấp | A4 | 45-48 | Cần bổ sung các trường lý lịch không có trong Excel nếu cơ sở yêu cầu |
| 12 | Kế hoạch đào tạo trung cấp/cao đẳng | 29,5 x 84 cm | 49-52 | Đã dựng 4 trang: thuyết minh, hướng dẫn, lịch 52 tuần/phân bổ và thi tốt nghiệp |
| 13 | Sổ cấp bằng trung cấp | 24 x 32 cm | 53-55 | Đã dựng bìa, trang chứng nhận và bảng 12 cột; cần nghiệm thu chữ/cỡ dòng |
| 14 | Sổ cấp bằng cao đẳng | 24 x 32 cm | 56-58 | Đã dựng bìa, trang chứng nhận và bảng 12 cột; cần nghiệm thu chữ/cỡ dòng |
| 15 | Sổ quản lý học sinh trung cấp | A4 | 59-63 | Đã dựng bìa, chứng nhận, lý lịch, kết quả từng năm và tốt nghiệp; cần đối chiếu pixel với file trống |
| 16 | Sổ quản lý sinh viên cao đẳng | A4 | 64-67 | Đã dựng bìa, chứng nhận, lý lịch, kết quả từng năm và tốt nghiệp; cần đối chiếu pixel với file trống |
| 17 | Sổ cấp bản sao bằng trung cấp | 24 x 32 cm | 68 và 72 | Đã dựng bìa, trang chứng nhận và bảng 11 cột đúng nhóm trường chính |
| 18 | Sổ cấp bản sao bằng cao đẳng | 24 x 32 cm | 69-73 | Đã dựng bìa, trang chứng nhận và bảng 11 cột đúng nhóm trường chính |

## Các giới hạn còn phải nghiệm thu

1. Các trang đã được dựng gần theo ảnh và PDF tham chiếu nhưng chưa thể tuyên bố giống nguyên mẫu để in chính thức nếu chưa đối chiếu với file mẫu trống của trường.
2. PDF/Excel đã bỏ dòng phân loại, watermark và footer thử nghiệm; chỉ còn nội dung biểu mẫu, dữ liệu đã nhập và các chỗ trống cần bổ sung.
3. Trường lý lịch mở rộng, điểm, chuyên cần và tốt nghiệp chỉ được điền khi Excel có sheet/cột tương ứng; hệ thống không tự bịa dữ liệu.
4. Với tên dài, nhiều lớp hoặc nhiều năm học, cần kiểm tra thêm phân trang thực tế trước khi xuất hàng loạt.
5. File Excel thử nghiệm không có đủ dữ liệu cho mọi phần Mẫu 1-7 và Mẫu 12; giao diện phải tiếp tục cảnh báo thiếu nguồn thay vì coi sheet tồn tại là đủ.

## Quy tắc triển khai thay thế

1. Cho xuất bản sạch để in/kiểm tra; vẫn phải nghiệm thu mẫu trống và căn cứ áp dụng trước khi ký hoặc dùng chính thức.
2. Mỗi bộ mẫu phải có mã phiên bản, căn cứ, đơn vị ban hành, ngày hiệu lực và file gốc đã duyệt.
3. Dùng file mẫu làm nguồn bố cục; chỉ chèn dữ liệu vào vùng được ánh xạ, không dựng layout chung.
4. Với mẫu dạng sổ, sinh đủ bìa, trang chứng nhận, các trang dữ liệu, phần ký và hướng dẫn.
5. Kiểm tra từng trang bằng ảnh render và chỉ mở lại từng mẫu sau khi người dùng/đơn vị nghiệm thu.
6. Các trường thiếu phải được báo trước khi xuất; không tự đặt nội dung trong bản chính thức.

## QA kỹ thuật lần gần nhất

Sau lần chỉnh sửa tiếp theo, bộ xuất dữ liệu được xuất lại từ workbook kiểm thử 5 người học bằng `tests/render_all.php --five`. Script `tests/inspect_pdfs.py` đã render toàn bộ trang bằng Poppler và kiểm tra kích thước vật lý; các PDF đều là A4, không có ký tự nằm ngoài ranh giới trang. Các nhóm bảng đã được kiểm tra bằng ảnh render gồm: tiến độ 52 tuần, kế hoạch giáo viên, sổ lên lớp, sổ tay/giáo án, kế hoạch đào tạo, sổ cấp bằng/chứng chỉ, sổ bản sao và sổ quản lý người học.

Bố cục được dựng theo bộ ảnh người dùng cung cấp và đã sửa các khung bìa A4, đường kẻ chấm, ô gộp, bảng kết quả từng năm, bảng tốt nghiệp và ô ảnh 3×4; trước khi in hoặc ký cần tiếp tục đối chiếu bản mẫu trống được trường phê duyệt.

## Thông tin cần đơn vị xác nhận

- Bộ mẫu trường đang áp dụng cho khóa tuyển sinh hiện tại và quyết định phê duyệt kèm theo.
- Tên đơn vị quản lý trực tiếp, tên trường, người ký và chức danh.
- Cách đánh quyển, số đăng ký, số trang và đóng dấu giáp lai.
- Có in đúng khổ gốc hay dùng khổ đã được trường phê duyệt chuyển đổi.
- Mẫu cần ưu tiên nghiệm thu đầu tiên.
