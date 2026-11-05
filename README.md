# HỆ THỐNG QUẢN LÝ PHÒNG THỰC HÀNH VÀ THIẾT BỊ

## 1. Giới thiệu đề tài
Xây dựng website hỗ trợ quản lý phòng thực hành và thiết bị, cho phép người dùng tra cứu phòng trống, gửi yêu cầu đặt phòng, quản lý thiết bị, báo hỏng và theo dõi lịch sử bảo trì.

**Công nghệ sử dụng:** PHP, MySQL, PDO, MVC.

## 2. Thành viên nhóm
- Phạm Diệu Thanh. MSV: 224001745
- Phạm Phương Thảo. MSV: 224001750
- Nguyễn Thu Trang. MSV: 224001756
- Ngô Nam Khánh. MSV: 224001725

## 3. Phân công công việc

### **Nguyễn Thu Trang: Quản lý đặt phòng và lịch phòng**
- Phân tích và xây dựng chức năng hiển thị lịch sử dụng phòng thực hành theo ngày, tuần và tháng, giúp người dùng dễ dàng theo dõi tình trạng đặt phòng.
- Xây dựng chức năng kiểm tra phòng trống theo khoảng thời gian được lựa chọn, hiển thị kết quả dựa trên lịch đặt phòng hiện có.
- Xây dựng chức năng gửi yêu cầu đặt phòng, xem danh sách các yêu cầu đã gửi và thực hiện hủy yêu cầu đặt phòng theo quy định.
- Xử lý logic kiểm tra tính hợp lệ của thời gian đặt phòng, đảm bảo thời gian kết thúc phải sau thời gian bắt đầu.
- Xây dựng cơ chế kiểm tra và ngăn chặn các trường hợp đặt trùng lịch đối với cùng một phòng thực hành.
- Xây dựng API kiểm tra tình trạng phòng trống, trả về dữ liệu JSON để phục vụ việc kiểm tra và cập nhật thông tin trên giao diện.

### **Phạm Phương Thảo: Quản lý phòng thực hành và thiết bị**
- Phân tích và xây dựng các chức năng quản lý thông tin phòng thực hành, bao gồm thêm mới, chỉnh sửa, xóa và hiển thị danh sách phòng.
- Xây dựng chức năng quản lý thông tin thiết bị, bao gồm thêm, sửa, xóa và tra cứu danh sách thiết bị thuộc từng phòng thực hành.
- Quản lý danh mục và loại thiết bị, đảm bảo thông tin thiết bị được phân loại và lưu trữ thống nhất trong hệ thống.
- Xây dựng chức năng tìm kiếm và lọc thiết bị theo tên, mã thiết bị, phòng thực hành, loại thiết bị và trạng thái hoạt động.
- Xử lý và cập nhật tình trạng hoạt động của thiết bị, phân biệt thiết bị đang hoạt động, hỏng hoặc đang được bảo trì.
- Bổ sung chức năng tạo và tra cứu mã QR cho thiết bị, hỗ trợ nhận diện nhanh và xem thông tin thiết bị trong hệ thống.

### **Phạm Diệu Thanh: Quản lý báo hỏng và bảo trì**
- Xây dựng chức năng cho phép người dùng gửi báo cáo khi thiết bị gặp sự cố hoặc không thể hoạt động bình thường.
- Xây dựng chức năng tiếp nhận, hiển thị và quản lý danh sách các báo cáo hỏng thiết bị trong hệ thống.
- Xử lý quy trình cập nhật trạng thái sự cố, theo dõi tiến độ xử lý từ khi tiếp nhận đến khi hoàn thành bảo trì.
- Xây dựng chức năng cập nhật kết quả sửa chữa, bảo trì và tình trạng hoạt động của thiết bị sau khi xử lý sự cố.
- Lưu trữ thông tin các lần báo hỏng và bảo trì, hỗ trợ tra cứu lịch sử sửa chữa của từng thiết bị.
- Kiểm soát quyền thực hiện các thao tác tiếp nhận và cập nhật kết quả bảo trì theo vai trò được phân quyền, đảm bảo chỉ người có thẩm quyền mới được phép xử lý.

### **Ngô Nam Khánh: Cơ sở dữ liệu, tài khoản, thống kê và tích hợp hệ thống**
- Phân tích yêu cầu nghiệp vụ và thiết kế cơ sở dữ liệu MySQL, xây dựng các bảng, khóa chính, khóa ngoại và mối quan hệ giữa các đối tượng trong hệ thống.
- Xây dựng cấu hình kết nối cơ sở dữ liệu bằng PHP PDO, triển khai các truy vấn sử dụng Prepared Statements nhằm đảm bảo an toàn dữ liệu.
- Xây dựng chức năng đăng nhập, đăng xuất, quản lý phiên đăng nhập và xác thực thông tin người dùng.
- Xây dựng cơ chế phân quyền theo vai trò, kiểm soát quyền truy cập vào các chức năng tương ứng của từng nhóm người dùng.
- Xây dựng giao diện Dashboard hiển thị các thông tin tổng quan và số liệu thống kê về phòng thực hành, thiết bị, tình trạng sử dụng và các hoạt động liên quan.
- Phối hợp tích hợp các module quản lý đặt phòng, quản lý thiết bị và quản lý bảo trì thành một hệ thống thống nhất.
- Kiểm tra, rà soát và hỗ trợ xử lý lỗi phát sinh trong quá trình tích hợp, đảm bảo các chức năng hoạt động đồng bộ.
- Chuẩn bị dữ liệu mẫu, kiểm thử các chức năng chính và hoàn thiện hệ thống phục vụ quá trình trình diễn, nghiệm thu dự án.