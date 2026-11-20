# Hướng dẫn sử dụng và chuyển máy chủ NTP_PLC

Tài liệu này dựa trên mã nguồn hiện có, rà soát ngày 20/11/2026. Ví dụ dùng PowerShell và thư mục `D:\xampp\htdocs\NTP_PLC`; đổi đường dẫn nếu máy mới cài ở ổ khác.

## 1. Hệ thống hoạt động như thế nào?

- Apache/PHP phục vụ ứng dụng Laravel; MySQL/MariaDB lưu tài khoản, cấu hình, dữ liệu PLC và sản xuất.
- Collector Python chạy riêng, đọc trang web PLC Siemens đã cấu hình và gửi dữ liệu đến API Laravel. Mẫu hiện tại dành cho M62; không mặc định áp dụng cho mọi PLC.
- Máy trạm mở trình duyệt vào địa chỉ máy chủ. Máy chạy collector phải truy cập được cả trang PLC lẫn API Laravel.

## 2. Trước khi chuyển máy

1. Chọn thời điểm dừng thu thập, ghi nhận thời gian; dừng collector cũ để tránh hai collector cùng gửi dữ liệu. Sao lưu trước khi thay đổi.
2. Sao chép mã nguồn cùng `composer.lock`, `package-lock.json`, thư mục `storage/app` và các tệp đính kèm cần dùng. Không dựa vào `app.zip` nếu chưa xác minh nó chứa bản mới nhất. Không sao chép `.venv` hoặc `node_modules` từ máy khác. Có thể cài lại `vendor` theo hướng dẫn dưới đây.
3. Sao lưu cơ sở dữ liệu đang chạy bằng phpMyAdmin: chọn đúng database → Export → Custom hoặc Quick → SQL, lưu tệp và kiểm tra dung lượng. Sao lưu cả `.env` Laravel và `collector\.env` qua kênh an toàn; hai tệp có thông tin bí mật. Giữ bản sao lưu ngoài máy cũ.
4. Giữ nguyên `APP_KEY` khi chuyển dữ liệu hiện có, vì dữ liệu mã hóa có thể phụ thuộc khóa này. Chỉ tạo khóa mới cho một hệ thống mới hoàn toàn. Ghi lại tên database, tài khoản, cổng MySQL, địa chỉ PLC và địa chỉ máy chủ mới. Không công khai token hoặc mật khẩu.
5. Cài XAMPP có PHP từ 8.2 và extension theo `composer.lock`; cài Composer, Node.js/npm và Python. Trong `D:\xampp\php\php.ini` bật các extension Composer yêu cầu, đặc biệt `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`, `gd`, `xml`; kiểm tra bằng `php -m` và `composer check-platform-reqs`. Node phù hợp với Vite 7; nếu npm báo không hỗ trợ phiên bản, dùng phiên bản Node được yêu cầu trong thông báo.

## 3. Cài lần đầu trên Windows 11

### 3.1 Khôi phục dữ liệu

Bật **Apache** và **MySQL** trong XAMPP Control Panel. Dùng phpMyAdmin tạo database rỗng, ví dụ `ntp_plc`, charset `utf8mb4`; chọn database đó → Import → chọn bản SQL vừa sao lưu. Kiểm tra các bảng, dữ liệu và bảng `migrations` đã được khôi phục. Tạo tài khoản database có quyền phù hợp và mật khẩu riêng.

**Quan trọng:** không chạy `migrate:fresh`, `db:wipe`, `migrate:reset` hoặc seed dữ liệu thử trên database thật. Mã hiện có hai migration `2026_07_30_000001` và `2026_07_30_100000` sửa bảng `plc_tags` trước migration tạo bảng `2026_07_30_150400`; vì vậy cài từ database trắng bằng `php artisan migrate` có thể thất bại. Tài liệu này yêu cầu bản sao lưu database cùng phiên bản hệ thống. Nếu không có bản sao lưu, cần sửa và kiểm thử thứ tự migration riêng trước khi tạo hệ thống mới.

### 3.2 Cài ứng dụng

Mở PowerShell tại máy mới:

```powershell
cd D:\xampp\htdocs\NTP_PLC
D:\xampp\php\php.exe -v
composer install --no-dev --optimize-autoloader
composer check-platform-reqs --no-dev
npm ci
npm run build
```

Nếu `php` trong PATH không phải PHP của XAMPP, sửa PATH để Composer dùng đúng `D:\xampp\php` rồi kiểm tra `where.exe php`. Composer và npm cần truy cập kho gói trong bước cài đặt. Không chạy `composer setup` trên bản chuyển: lệnh đó tạo khóa và chạy migration.

Khôi phục `.env` đã sao lưu, sửa các giá trị theo máy mới. Ví dụ dưới đây là cấu hình minh họa, không thay `APP_KEY` cũ:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.10
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ntp_plc
DB_USERNAME=ntp_plc_user
DB_PASSWORD=mat_khau_thuc_te
```

Thay IP, tên và mật khẩu bằng thông tin thực tế. Nếu mật khẩu chứa ký tự đặc biệt, dùng cú pháp `.env` có dấu ngoặc kép phù hợp. Giữ các cấu hình session/cache/queue tương thích với database đã khôi phục. Bảo đảm tài khoản chạy Apache có quyền ghi `storage` và `bootstrap\cache`.

```powershell
D:\xampp\php\php.exe artisan optimize:clear
D:\xampp\php\php.exe artisan migrate:status
```

Chỉ chạy `php artisan migrate --force` sau khi đã sao lưu và xác nhận các migration pending phù hợp với bản database; không chạy mù trên bản mới khôi phục. Không chạy `db:seed` trên dữ liệu thật: `DatabaseSeeder` gọi `AdminUserSeeder`, có thể đặt lại mật khẩu một tài khoản quản trị về giá trị mặc định trong mã. Kiểm tra và đổi mật khẩu tài khoản đó, nếu tồn tại.

### 3.3 Cấu hình Apache

Nên trỏ DocumentRoot trực tiếp vào thư mục `public`, không mở thư mục gốc dự án ra mạng. Ví dụ VirtualHost trong cấu hình Apache của XAMPP, thay đường dẫn và tên máy cho đúng:

```apache
<VirtualHost *:80>
    ServerName plc-server.local
    DocumentRoot "D:/xampp/htdocs/NTP_PLC/public"
    <Directory "D:/xampp/htdocs/NTP_PLC/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Bật `mod_rewrite`, nạp cấu hình VirtualHost trong Apache nếu chưa bật, kiểm tra `httpd.exe -t` bằng đúng Apache của XAMPP, rồi khởi động lại Apache. Đảm bảo tên `plc-server.local` phân giải đến IP máy chủ trên từng máy trạm, hoặc cấu hình VirtualHost theo địa chỉ thực tế; cập nhật `APP_URL` tương ứng. Kiểm tra bằng trình duyệt `http://plc-server.local/up`, sau đó mở `/login`. Với mạng nội bộ, chỉ mở cổng web trong Windows Firewall cho mạng cần thiết; không đưa cổng MySQL hay trang quản trị XAMPP ra ngoài nếu không cần. Dùng HTTPS và kiểm soát truy cập khi triển khai thực tế.

## 4. Cài và kiểm tra collector

Trong PowerShell, sau khi khôi phục `collector\.env` hoặc tạo từ mẫu:

```powershell
cd D:\xampp\htdocs\NTP_PLC\collector
python --version
Set-ExecutionPolicy -Scope Process Bypass
.\install.ps1
notepad .env
```

Cấu hình `PLC_CODE` khớp PLC trong ứng dụng, `PLC_URL` đúng trang variable table của PLC, `LARAVEL_BASE_URL` là gốc URL Apache, ví dụ `http://127.0.0.1` nếu VirtualHost mặc định xử lý đúng host, hoặc `http://plc-server.local`; không thêm `/api`. Điền `COLLECTOR_TOKEN` tương ứng. Mẫu mặc định `127.0.0.1:8000` chỉ phù hợp khi đang chạy `artisan serve` ở cổng 8000, không tự động trỏ đến Apache. Các giá trị IP, URL PLC trong mẫu chỉ là ví dụ cần xác minh thực tế.

Nếu không còn token gốc, dùng tài khoản có quyền sửa PLC để tạo lại token trong giao diện PLC, lưu ngay token mới vào `collector\.env`; tạo lại sẽ vô hiệu hóa token cũ. Bảo vệ tệp này. Kiểm tra kết nối PLC và API, rồi chạy lần lượt:

```powershell
.\.venv\Scripts\python.exe collector.py --dry-run
.\.venv\Scripts\python.exe collector.py --heartbeat
.\.venv\Scripts\python.exe collector.py --once
```

`--dry-run` chỉ thử đọc; heartbeat kiểm tra kết nối API; `--once` gửi một chu kỳ và ghi dữ liệu thật. Xem `logs\collector.log` và giao diện theo dõi để xác nhận giá trị, thời gian cập nhật và chất lượng dữ liệu. Sau đó chạy liên tục bằng lệnh sau, giữ cửa sổ mở:

```powershell
.\.venv\Scripts\python.exe collector.py
```

Dừng bằng `Ctrl+C`. Chỉ chạy một bản collector cho mỗi cấu hình PLC. Để tự chạy khi bật Windows, sau khi kiểm tra thủ công thành công, dừng bản chạy tay, mở PowerShell **Run as administrator** và chạy:

```powershell
cd D:\xampp\htdocs\NTP_PLC\collector
Set-ExecutionPolicy -Scope Process Bypass
.\install_collector_task.ps1 -ProjectPath 'D:\xampp\htdocs\NTP_PLC'
.\check_collector_task.ps1 -ProjectPath 'D:\xampp\htdocs\NTP_PLC'
```

Script đăng ký Scheduled Task `NTP_PLC_Collector_M62`, chạy bằng SYSTEM khi khởi động và thay thế task cùng tên nếu đã có. Kiểm tra task, log và dữ liệu sau khi khởi động lại; quyền SYSTEM, mạng và thứ tự khởi động Apache/MySQL có thể ảnh hưởng. Task chỉ khởi động collector, không tự bật Apache/MySQL.

## 5. Mỗi lần bật máy chủ

1. Mở XAMPP Control Panel, Start **MySQL** và **Apache**, kiểm tra đều đang chạy và không xung đột cổng.
2. Mở `http://plc-server.local/up` và `/login`. Từ máy trạm dùng tên hoặc IP thực tế của máy chủ.
3. Nếu đã cài Scheduled Task, kiểm tra task và log bằng lệnh `check_collector_task.ps1` ở mục 4. Nếu chưa cài, chạy collector thủ công như mục 4 và giữ cửa sổ PowerShell mở.
4. Đăng nhập, kiểm tra dữ liệu PLC có thời gian cập nhật mới. Không cần chạy `composer install`, `npm run build`, migration hay `artisan serve` mỗi lần khởi động Apache.

## 6. Sử dụng phần mềm

Các chức năng hiển thị tùy quyền tài khoản. Đăng nhập tại `/login` bằng tài khoản đang có; quản trị viên cấp quyền qua quản lý người dùng và vai trò. Tài khoản bị khóa hoặc thiếu quyền có thể không truy cập được chức năng.

- Khai báo theo thứ tự **nhà máy → dây chuyền → máy → PLC**. Tạo hoặc đồng bộ tag PLC; kiểm tra địa chỉ, kiểu dữ liệu, đơn vị và trạng thái trước khi dùng.
- Vào màn hình giám sát máy để xem trạng thái và giá trị; màn hình công nghệ có dữ liệu và biểu đồ xu hướng khi tài khoản có quyền. Cấu hình bố cục và vị trí tag tại phần quản lý tag/bố cục máy.
- Khai báo sản phẩm, ca sản xuất, định mức sản phẩm theo máy và lý do dừng. Cấu hình tag/chỉ số OEE cho máy trước khi đối chiếu báo cáo.
- Lập khai báo sản xuất theo ca, điền thông tin và sản lượng/dừng máy, lưu và gửi; người có quyền có thể duyệt, trả lại, khóa hoặc mở khóa theo quy trình. Có chức năng tính lại OEE khi được cấp quyền.
- Xem Dashboard, báo cáo OEE và báo cáo sản xuất chi tiết theo ngày; xuất Excel khi có quyền. Kiểm tra dữ liệu đầu vào và khoảng thời gian trước khi sử dụng báo cáo.
- Quản lý người dùng, vai trò và nhật ký hoạt động bằng tài khoản được phân quyền. Đổi mật khẩu qua hồ sơ tài khoản.

## 7. Kiểm tra lỗi thường gặp

- **Không vào được web:** kiểm tra Apache, cổng, DNS/IP và firewall; chạy `D:\xampp\apache\bin\httpd.exe -t`. Lỗi 500: xem `storage\logs\laravel.log` và log Apache; kiểm tra quyền ghi, `.env` và kết nối database.
- **Không kết nối database:** kiểm tra MySQL, cổng, tài khoản và database; chạy `php artisan migrate:status` sau khi xác nhận PHP/PATH. Lệnh này chỉ xem trạng thái, không sửa database.
- **Không có dữ liệu PLC:** kiểm tra địa chỉ PLC từ máy collector, token, URL API, task và `collector\logs\collector.log`. HTTP 401 thường liên quan token; kiểm tra thông báo cụ thể trong log. Khi đổi `.env` collector, khởi động lại tiến trình/task.
- **Giao diện thiếu CSS/JS:** kiểm tra `npm run build`, thư mục `public/build`, cấu hình Apache và xóa `public/hot` cũ nếu là tệp còn sót từ môi trường phát triển.
- **403:** kiểm tra trạng thái tài khoản và quyền tương ứng; không tự cấp toàn quyền cho mọi người.

Sao lưu định kỳ database và các tệp cần thiết, giữ riêng thông tin cấu hình/khóa, và thử khôi phục trên máy kiểm thử. Không ghi token, mật khẩu vào tài liệu dùng chung.
