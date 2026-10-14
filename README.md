# HỆ THỐNG QUẢN LÝ PHÒNG THỰC HÀNH VÀ THIẾT BỊ

## 1. Giới thiệu đề tài
Xây dựng website hỗ trợ quản lý phòng thực hành và thiết bị, cho phép người dùng tra cứu phòng trống, gửi yêu cầu đặt phòng, quản lý thiết bị, báo hỏng và theo dõi lịch sử bảo trì.

**Công nghệ sử dụng:** PHP, MySQL, PDO, MVC.

## 2. Thành viên nhóm
- Nguyễn Thu Trang. MSV: 224001756
- Phạm Phương Thảo. MSV: 224001750
- Phạm Diệu Thanh. MSV: 224001745
- Ngô Nam Khánh. MSV: 224001725

## 3. Phân công công việc

### Thành viên 1: Quản lý đặt phòng và lịch phòng
- Xây dựng chức năng xem lịch phòng và kiểm tra phòng trống theo thời gian.
- Xây dựng chức năng gửi, xem và hủy yêu cầu đặt phòng.
- Xử lý quy tắc thời gian đặt phòng và kiểm tra booking trùng lịch.
- Xây dựng endpoint JSON kiểm tra tình trạng phòng trống.

### Thành viên 2: Quản lý phòng thực hành và thiết bị
- Xây dựng chức năng thêm, sửa, xóa và xem danh sách phòng thực hành.
- Quản lý loại thiết bị và danh sách thiết bị.
- Xây dựng chức năng tìm kiếm, lọc theo phòng, loại thiết bị và trạng thái.
- Quản lý tình trạng hoạt động của thiết bị.

### Thành viên 3: Quản lý báo hỏng và bảo trì
- Xây dựng chức năng gửi và tiếp nhận báo hỏng thiết bị.
- Quản lý trạng thái xử lý sự cố và kết quả bảo trì.
- Lưu trữ, tra cứu lịch sử bảo trì và sửa chữa.
- Kiểm soát quyền cập nhật kết quả bảo trì theo vai trò người dùng.

### Thành viên 4: Cơ sở dữ liệu, tài khoản, thống kê và tích hợp
- Thiết kế cơ sở dữ liệu MySQL và xây dựng kết nối bằng PDO.
- Xây dựng chức năng đăng nhập, đăng xuất và phân quyền người dùng.
- Xây dựng dashboard thống kê tình trạng phòng và thiết bị.
- Tích hợp các module, kiểm thử hệ thống và chuẩn bị dữ liệu demo.
