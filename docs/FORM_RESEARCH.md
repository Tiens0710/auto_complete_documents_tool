# Nghiên cứu biểu mẫu cho AIVANBAN

Ngày kiểm tra nguồn: 08/09/2026.

## 1. Kết luận dùng để thiết kế phần mềm

Không xây hệ thống bằng cách gắn cứng toàn bộ 18 mẫu của Quyết định 62/2008/QĐ-BLĐTBXH.

- Quyết định 62/2008 là bộ mẫu lịch sử trong hai ảnh người dùng gửi.
- Thông tư 23/2018/TT-BLĐTBXH có hiệu lực từ 21/01/2019, bãi bỏ Quyết định 62/2008 đối với hồ sơ, sổ sách đào tạo trình độ trung cấp và cao đẳng.
- Thông tư 23/2018 quy định nội dung tối thiểu nhưng giao hiệu trưởng quy định biểu mẫu cụ thể; khuyến khích hồ sơ điện tử và yêu cầu có thể trích xuất thành bản giấy.
- Thông tư 42/2015/TT-BLĐTBXH từng ban hành các mẫu sơ cấp chi tiết. Thông tư 34/2018/TT-BLĐTBXH, có hiệu lực từ 08/02/2019, thay Điều 32 theo hướng người đứng đầu cơ sở đào tạo sơ cấp được tự chủ quy định hồ sơ, sổ sách dựa trên các loại và nội dung bắt buộc.
- Vì vậy, phần mềm phải có cơ chế **template có phiên bản** và **ánh xạ trường động**. Mẫu do đơn vị đang dùng mới là đầu vào quyết định bố cục xuất cuối cùng.

Các mẫu cũ vẫn có thể cần thiết cho hồ sơ lịch sử hoặc nếu đơn vị chủ động tiếp tục dùng bố cục đó. Khi ấy phải đánh dấu template là `legacy`, ghi rõ căn cứ, phạm vi khóa học và ngày hiệu lực.

## 2. Nguồn đã xác minh

### Nguồn chính thức

- CSDL quốc gia về VBPL - Quyết định 62/2008: <https://vbpl.vn/TW/Pages/vbpq-luocdo.aspx?ItemID=123094>
- CSDL quốc gia về VBPL - Thông tư 42/2015: <https://vbpl.vn/TW/Pages/vbpq-thuoctinh.aspx?ItemID=96229>
- Công báo Chính phủ - Thông tư 42/2015: <https://congbao.chinhphu.vn/van-ban/thong-tu-so-42-2015-tt-bldtbxh-18343/12910.htm>
- CSDL quốc gia về VBPL - Thông tư 23/2018: <https://vbpl.vn/TW/Pages/vbpq-toanvan.aspx?ItemID=132621>
- Công báo Chính phủ - Thông tư 23/2018: <https://congbao.chinhphu.vn/van-ban/thong-tu-so-23-2018-tt-bldtbxh-27902/24882.htm>
- Công báo Chính phủ - Thông tư 34/2018: <https://congbao.chinhphu.vn/van-ban/thong-tu-so-34-2018-tt-bldtbxh-29045.htm>

### Bản PDF lưu tại dự án

- `docs/references/TT-42-2015-BLDTBXH.pdf`: bản Công báo, 55 trang; các mẫu nằm ở trang PDF 27-55, trong đó mẫu quản lý đào tạo nằm ở trang PDF 29-54.
- `docs/references/TT-23-2018-BLDTBXH.pdf`: bản Công báo, 6 trang.
- `docs/references/TT-34-2018-BLDTBXH.pdf`: bản Công báo, 24 trang; phần sửa đổi Thông tư 42 nằm chủ yếu ở trang PDF 2-12.

Không dùng một bài đăng tổng hợp trên website thương mại làm chuẩn bố cục khi đã có bản Công báo hoặc mẫu do đơn vị cung cấp.

### Bản đối chiếu bộ 18 mẫu lịch sử

- Bản toàn văn có nội dung bảng: <https://luatvietnam.vn/giao-duc/quyet-dinh-62-2008-qd-bldtbxh-bo-lao-dong-thuong-binh-va-xa-hoi-38867-d1.html>
- Bản ảnh 79 trang dùng để kiểm tra lại bố cục và các nhãn cột bị mất khi trích xuất văn bản: <https://www.slideshare.net/slideshow/622008-qdbldtbxh/58595200>

Hai nguồn trên chỉ dùng để đọc phụ lục lịch sử của Quyết định 62/2008; tình trạng hiệu lực vẫn được xác nhận bằng CSDL quốc gia về VBPL và các bản Công báo chính thức.

## 3. Bản đồ đầy đủ 18 mẫu theo Quyết định 62/2008 (legacy)

Đã đọc và đối chiếu đủ 18 mẫu, bao gồm bìa, trang chứng nhận, bảng dữ liệu, phần hướng dẫn và các trang con của những mẫu dạng sổ. Danh mục dưới đây là bản đồ nghiệp vụ để thiết kế dữ liệu, **không phải kết luận rằng bố cục năm 2008 còn bắt buộc áp dụng**.

### Nhóm dùng chung

1. **Mẫu 1 - Tiến độ đào tạo (A2):** cơ sở, năm học, lớp; lịch theo tháng/tuần và khoảng ngày; các trạng thái khai/bế giảng, văn hóa THPT, môn chung, môn học/mô-đun nghề, thi tốt nghiệp hoặc kiểm tra cuối khóa, nghỉ hè/lễ, lao động/ngoại khóa, thực tập doanh nghiệp; ghi chú và chữ ký hiệu trưởng/giám đốc, trưởng phòng đào tạo.
2. **Mẫu 2 - Kế hoạch giáo viên (A2):** năm học, họ tên giáo viên; bố trí giảng dạy theo tháng/tuần, môn học/mô-đun, lớp, số giờ; nhiệm vụ khác và giờ quy đổi; tổng giờ giảng, giờ tiêu chuẩn, giờ thừa/thiếu; chữ ký phê duyệt.
3. **Mẫu 3 - Sổ lên lớp (26 x 38,5 cm):** bìa lớp/trình độ/nghề/khóa/năm; danh sách giáo viên và giáo viên chủ nhiệm; thời khóa biểu; chuyên cần theo ngày và giờ nghỉ có phép/không phép; nhật ký nội dung bài dạy; điểm định kỳ, kết thúc và tổng kết mô-đun; rèn luyện; tổng hợp học tập; đánh giá cuối năm/cuối khóa; kiểm tra tình hình dạy học và hướng dẫn sử dụng.
4. **Mẫu 4 - Sổ tay giáo viên (19 x 26,5 cm):** bìa môn học/mô-đun, lớp, khóa, giáo viên, năm học; thông tin lớp, đầu vào, quyết định thành lập, sĩ số và tổ chức lớp; kết quả học tập; giờ nghỉ theo tháng và tổng hợp; học sinh/sinh viên cá biệt; đánh giá quá trình giảng dạy.
5. **Mẫu 5 - Giáo án lý thuyết (19 x 26,5 cm):** số giáo án, thời gian, chương, ngày dạy, tên bài, mục tiêu, phương tiện; ổn định lớp, kiểm tra bài cũ, nội dung bài mới theo hoạt động giáo viên/người học và thời gian, củng cố, hướng dẫn tự học, tài liệu tham khảo, rút kinh nghiệm và chữ ký.
6. **Mẫu 6 - Giáo án thực hành (19 x 26,5 cm):** số giáo án, thời gian, bài trước, ngày thực hiện, tên bài, mục tiêu, phương tiện, hình thức tổ chức; ổn định lớp; dẫn nhập; hướng dẫn ban đầu, thường xuyên, kết thúc và tự rèn luyện; hoạt động giáo viên/học sinh, thời gian, rút kinh nghiệm và chữ ký.
7. **Mẫu 7 - Giáo án tích hợp (19 x 26,5 cm):** thông tin đầu giáo án tương tự mẫu 6; ổn định lớp; dẫn nhập, giới thiệu chủ đề, giải quyết vấn đề, kết thúc vấn đề/củng cố kiến thức-kỹ năng, hướng dẫn tự học; hoạt động hai phía, thời gian, rút kinh nghiệm và chữ ký.

### Nhóm sơ cấp nghề

8. **Mẫu 8 - Kế hoạch đào tạo (29,5 x 42 cm):** cơ sở, nghề/mã nghề, lớp, đối tượng, mục tiêu, thời gian khóa và thực học, khai/bế giảng, quyết định chương trình; phân bổ lý thuyết-thực hành-ôn/kiểm tra từng môn/mô-đun và lịch kiểm tra; bài tập kỹ năng tổng hợp, điều kiện, phương pháp đánh giá cuối khóa; ngày và người phê duyệt.
9. **Mẫu 9 - Sổ cấp chứng chỉ sơ cấp (24 x 32 cm):** bìa/quyển số; chứng nhận số trang, dải số đăng ký, ngày mở/khóa sổ; bảng số thứ tự, họ tên học sinh, ngày sinh, nơi sinh, nghề, khóa và thời gian khóa, quyết định tốt nghiệp, số hiệu chứng chỉ, ngày cấp, ngày nhận, chữ ký, ghi chú.
10. **Mẫu 10 - Sổ cấp bản sao chứng chỉ từ sổ gốc (24 x 32 cm):** bìa và chứng nhận sổ; bảng họ tên, ngày sinh, nơi sinh, quyết định tốt nghiệp, số hiệu chứng chỉ, số lượng bản sao, hình thức/ngày cấp, số sổ-trang của bản chính và chữ ký người nhận.
11. **Mẫu 11 - Sổ quản lý học sinh sơ cấp:** mỗi học sinh có số đăng ký và ảnh 3 x 4; họ tên, tên thường gọi, giới tính, ngày/nơi sinh, quê quán, thường trú, dân tộc, tôn giáo, học vấn, thông tin Đảng/Đoàn, bố/mẹ/vợ chồng và nghề nghiệp, diện chính sách, nghề trước học, liên hệ và việc làm sau khóa; lớp/khóa/thời gian; điểm từng môn/mô-đun và kiểm tra cuối khóa, rèn luyện, nhận xét, điểm/xếp loại tốt nghiệp, quyết định và chứng chỉ.

### Nhóm trung cấp nghề và cao đẳng nghề

12. **Mẫu 12 - Kế hoạch đào tạo (29,5 x 84 cm):** nghề/mã nghề, trình độ, đối tượng, mục tiêu, khóa học và thời gian, quyết định chương trình; lịch toàn khóa theo 52 tuần cho năm I-IV và các loại hoạt động; phân bổ giờ lý thuyết, thực hành, thi theo học kỳ/năm; nội dung, thời gian ôn/thi, kế hoạch, hình thức và phương pháp đánh giá thi tốt nghiệp; phê duyệt.
13. **Mẫu 13 - Sổ cấp bằng tốt nghiệp trung cấp nghề (24 x 32 cm):** bìa/chứng nhận sổ; họ tên học sinh, ngày/nơi sinh, nghề, khóa, hình thức đào tạo, quyết định công nhận tốt nghiệp, số hiệu bằng, ngày cấp, ngày nhận, chữ ký và ghi chú.
14. **Mẫu 14 - Sổ cấp bằng tốt nghiệp cao đẳng nghề (24 x 32 cm):** cấu trúc như mẫu 13 nhưng đối tượng là sinh viên và bằng cao đẳng nghề.
15. **Mẫu 15 - Sổ quản lý học sinh trung cấp nghề (A4):** bìa/chứng nhận; lý lịch và ảnh 3 x 4; học vấn, thông tin gia đình/chính sách/liên hệ/nguyện vọng việc làm; kết quả từng năm-niên khóa theo môn/mô-đun, điểm định kỳ/kết thúc/tổng kết, xếp loại học tập-rèn luyện, khen thưởng/kỷ luật; thi tốt nghiệp lần 1/lần 2, nhận xét toàn khóa, điểm trung bình/xếp loại, quyết định tốt nghiệp, số bằng, ngày cấp và người tổng hợp.
16. **Mẫu 16 - Sổ quản lý sinh viên cao đẳng nghề (A4):** cùng nhóm dữ liệu với mẫu 15 nhưng dùng cho sinh viên cao đẳng nghề.
17. **Mẫu 17 - Sổ cấp bản sao bằng trung cấp nghề từ sổ gốc (24 x 32 cm):** bìa/chứng nhận; họ tên học sinh, ngày/nơi sinh, quyết định tốt nghiệp, số hiệu bằng, số sổ-trang bản chính, hình thức/ngày cấp bản sao, số lượng và chữ ký người nhận.
18. **Mẫu 18 - Sổ cấp bản sao bằng cao đẳng nghề từ sổ gốc (24 x 32 cm):** cấu trúc như mẫu 17 nhưng đối tượng là sinh viên và bằng cao đẳng nghề.

### Kết luận sau khi đọc đủ bộ cũ

- Bộ 18 mẫu chứa ba loại dữ liệu khác nhau: dữ liệu dùng chung của cơ sở/lớp/khóa; dữ liệu cá nhân người học; và dữ liệu lặp theo thời gian như điểm, chuyên cần, lịch học, lần cấp bằng/chứng chỉ.
- Một file Excel chỉ có thông tin sinh viên sẽ tự động điền tốt các mẫu 9-11 và 13-18, nhưng không đủ để hoàn thành mẫu 1-8 và 12 vì còn cần kế hoạch đào tạo, giáo viên, môn học, lịch, điểm, chuyên cần và nội dung giảng dạy.
- Hệ thống nhập liệu nên hỗ trợ nhiều bảng/sheet hoặc nhiều đợt nhập liên kết bằng mã học sinh/sinh viên, mã lớp, mã khóa và mã môn/mô-đun.
- Khi người dùng cung cấp mẫu đang áp dụng tại trường, phải đối chiếu từng trường và bố cục với bản đồ này, không tự động chọn bản năm 2008.

## 4. Bộ mẫu sơ cấp trong phụ lục Thông tư 42/2015

Các mẫu dưới đây đã được kiểm tra bằng cả trích xuất văn bản và xem trực quan toàn bộ các trang liên quan. Đây là cấu trúc tham khảo chính xác của phụ lục năm 2015, nhưng việc dùng làm mẫu hiện tại phải xét cùng Thông tư 34/2018 và mẫu do cơ sở phê duyệt.

### Mẫu số 3 - Tiến độ đào tạo

Khổ trình bày ngang. Các trường chính:

- Cơ sở đào tạo sơ cấp, lớp học, khóa học.
- Trục tháng, tuần 1-52, từ ngày, đến ngày.
- Nội dung hoạt động: khai/bế giảng, hoạt động chung, mô-đun đào tạo nghề, thực tập tại doanh nghiệp, kiểm tra hoặc thi kết thúc khóa học, nghỉ lễ, lao động/ngoại khóa.
- Ghi chú, ngày lập, hiệu trưởng/giám đốc và trưởng phòng đào tạo.

### Mẫu số 4 - Kế hoạch giáo viên

Khổ trình bày ngang. Các trường chính:

- Cơ sở đào tạo, năm học, khóa học.
- Giáo viên; bố trí giảng dạy theo tháng/tuần; mô-đun; lớp; số giờ giảng.
- Nhiệm vụ khác, nội dung và số giờ quy đổi.
- Tổng giờ giảng trong kỳ, giờ tiêu chuẩn, giờ thừa, giờ thiếu.
- Hiệu trưởng/giám đốc và trưởng khoa/bộ môn.

### Mẫu số 5 - Sổ lên lớp

Bìa ghi đơn vị quản lý, cơ sở đào tạo, lớp, trình độ, nghề, khóa và năm học; khổ ghi trên mẫu là `26 x 38,5 cm`.

Sổ gồm 10 phần:

1. Danh sách giáo viên giảng dạy và giáo viên chủ nhiệm.
2. Thời khóa biểu từ thứ Hai đến Chủ nhật, nội dung và thời gian.
3. Theo dõi ngày học tập theo từng học sinh, ngày trong tháng, giờ nghỉ có phép/không phép.
4. Tóm tắt nội dung buổi học: giáo viên, mô-đun, ngày, giờ lý thuyết/thực hành/kiểm tra, nội dung, học sinh vắng và chữ ký.
5. Bảng điểm từng mô-đun: điểm thường xuyên/định kỳ, điểm kết thúc lần 1/lần 2, điểm mô-đun.
6. Xếp loại rèn luyện theo kỳ/đợt.
7. Tổng hợp kết quả học tập theo mô-đun và mô-đun phải học lại.
8. Tổng hợp cuối khóa: giờ nghỉ, điểm trung bình, rèn luyện, mô-đun học lại.
9. Kiểm tra tình hình dạy học.
10. Hướng dẫn sử dụng; ký hiệu vắng có lý do là `P`, không lý do là `K` theo mẫu năm 2015.

### Mẫu số 6 - Sổ tay giáo viên

- Bìa: đơn vị, cơ sở, mô-đun, lớp, nghề, giáo viên và khóa.
- Thông tin lớp/khóa: nghề, trình độ, đầu vào, quyết định thành lập lớp, sĩ số, giáo viên chủ nhiệm, lớp trưởng/lớp phó/tổ trưởng và phương thức tổ chức.
- Kết quả học tập theo học sinh: điểm thường xuyên/định kỳ, điểm kết thúc lần 1/lần 2 và điểm mô-đun.
- Tổng hợp số giờ nghỉ theo kỳ/đợt.
- Quản lý học sinh cá biệt.
- Đánh giá quá trình giảng dạy mô-đun.

### Mẫu số 7 - Kế hoạch đào tạo

- Cơ sở đào tạo, nghề và mã nghề, lớp, khóa, trình độ sơ cấp, đối tượng tuyển sinh.
- Mục tiêu: kiến thức, kỹ năng nghề, năng lực tự chủ và trách nhiệm.
- Thời gian khóa học, thời gian thực học, thời gian kiểm tra/thi, khai giảng/bế giảng.
- Quyết định phê duyệt chương trình.
- Phân bổ thời gian từng mô-đun: lý thuyết, thực hành, ôn/kiểm tra và lịch kiểm tra.
- Kiểm tra/thi kết thúc khóa học: bài tập kỹ năng tổng hợp, điều kiện, phương pháp đánh giá và ghi chú.
- Người phê duyệt và ngày ký.

Lưu ý: Thông tư 34/2018 thay đổi quy định tốt nghiệp và đưa thêm khái niệm tín chỉ; không mặc định giữ nguyên mọi logic kiểm tra cuối khóa trong mẫu năm 2015.

### Mẫu số 8 - Sổ cấp chứng chỉ sơ cấp

- Bìa và trang chứng nhận: đơn vị, cơ sở, quyển số, số trang, dải số đăng ký, ngày mở/khóa sổ, người ký.
- Bảng dữ liệu: số thứ tự, họ tên học sinh, ngày sinh, số hiệu chứng chỉ, ngày nhận, chữ ký người nhận, ghi chú.
- Theo Thông tư 34/2018, sổ phải ghi chính xác nội dung tiếng Việt như bản chính, đánh số trang, đóng dấu giáp lai, không tẩy xóa và lưu trữ vĩnh viễn.

### Mẫu số 9 - Sổ cấp bản sao chứng chỉ sơ cấp

- Phần bìa/chứng nhận tương tự Mẫu số 8.
- Bảng dữ liệu: số thứ tự, họ tên, ngày sinh, số hiệu chứng chỉ, số bản sao, ngày nhận, chữ ký người nhận, ghi chú.

### Mẫu số 10 - Sổ quản lý học sinh

Mỗi học sinh một trang. Các trường chính:

- Số đăng ký và ảnh `4 x 6`.
- Họ tên khai sinh, tên thường gọi, giới tính, ngày sinh.
- Quê quán, nơi đăng ký thường trú, tạm trú.
- Dân tộc, tôn giáo, trình độ học vấn trước khi vào học.
- Ngày vào Đảng/ngày chính thức, ngày vào Đoàn.
- Họ tên và nghề nghiệp của bố, mẹ, vợ/chồng.
- Diện đối tượng, nghề nghiệp trước khi học.
- Người cần báo tin, điện thoại, địa chỉ liên lạc, nơi làm việc sau khóa học.
- Lớp, khóa, thời gian đào tạo.
- Kết quả từng mô-đun, điểm kết thúc lần 1/lần 2, điểm xếp loại tốt nghiệp.
- Nhận xét, quyết định công nhận tốt nghiệp, số và ngày cấp chứng chỉ.

## 5. Hồ sơ trung cấp và cao đẳng theo Thông tư 23/2018

### Hồ sơ, sổ sách dành cho trường

1. Chương trình đào tạo.
2. Kế hoạch đào tạo.
3. Tiến độ đào tạo.
4. Thời khóa biểu.
5. Sổ lên lớp.
6. Sổ quản lý học sinh, sinh viên.
7. Sổ theo dõi đào tạo tại doanh nghiệp.
8. Sổ cấp bằng tốt nghiệp.

### Hồ sơ dành cho giáo viên, giảng viên

1. Kế hoạch giảng dạy.
2. Giáo án lý thuyết, thực hành hoặc tích hợp.
3. Sổ tay giáo viên.

Thông tư quy định nội dung tối thiểu cho từng loại nhưng không ban hành một bộ bảng cố định giống Quyết định 62/2008. Hiệu trưởng quy định mẫu cụ thể. Phần mềm phải hỗ trợ mẫu của từng trường và có thể thay đổi theo phiên bản.

## 6. Yêu cầu kỹ thuật rút ra từ quy định

- Mỗi template phải có: `code`, `name`, `education_level`, `legal_basis`, `institution_id`, `version`, `effective_from`, `effective_to`, `status` và file mẫu nguồn.
- Tách trường chuẩn dùng chung khỏi tên cột Excel và vị trí hiển thị trong từng template.
- Một trường chuẩn có thể có nhiều bí danh Excel và nhiều vị trí xuất trên một hoặc nhiều mẫu.
- Dữ liệu có tính lặp phải dùng cấu trúc danh sách: học sinh, mô-đun, điểm, buổi học, ngày nghỉ, giáo viên, lần cấp chứng chỉ.
- Bản xuất phải in được, giữ đúng khổ giấy/hướng giấy của template đang dùng và có số trang ổn định.
- Hồ sơ điện tử phải có phân quyền, nhật ký thay đổi, sao lưu, khóa phiên bản đã duyệt và khả năng trích xuất bản giấy.
- Không cho sửa âm thầm các sổ cấp chứng chỉ. Mọi điều chỉnh phải có lịch sử và người thực hiện.
- Các trường pháp lý, quy tắc điểm, ký hiệu chuyên môn và quy tắc tốt nghiệp phải cấu hình theo trình độ, niên khóa và văn bản áp dụng; không viết cứng thành một quy tắc toàn hệ thống.

## 7. Việc còn cần người dùng cung cấp

Để bắt đầu code một mẫu có thể nghiệm thu, vẫn cần:

- Xác nhận phần mềm phục vụ sơ cấp, trung cấp, cao đẳng hay nhiều trình độ.
- File Word/Excel/PDF hoặc bản scan trống của mẫu đang dùng tại đơn vị.
- Quyết định/phê duyệt nội bộ về mẫu nếu đang dùng mẫu riêng theo quyền tự chủ.
- Một file Excel mẫu đã thay dữ liệu cá nhân bằng dữ liệu giả.
- Xác nhận mẫu đầu tiên cần tự động hóa và định dạng xuất mong muốn.

Không thể bảo đảm đúng bố cục chỉ bằng hai ảnh chụp trang mục lục của Quyết định 62/2008.
