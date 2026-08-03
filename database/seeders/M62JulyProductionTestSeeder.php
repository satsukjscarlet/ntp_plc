<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MachineProductStandard;
use App\Models\Product;
use App\Models\ProductionShift;
use App\Models\ShiftProductionDeclaration;
use App\Models\ShiftProductionDeclarationItem;
use App\Models\User;
use App\Services\OeeCalculationService;
use App\Services\PlcOeeMetricService;
use App\Services\ShiftTimeService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class M62JulyProductionTestSeeder extends Seeder
{
    private const MACHINE_CODE = 'M62';

    private const SOURCE_JSON = <<<'JSON'
[{"date":"2026-07-07","code":"P6310","name":"ống 63pn10pe80","unit":"M","weight_g":873.0,"group":"PE80","ca1":0.0,"ca2":0.0,"ca3":250.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":218250.0,"note":""},{"date":"2026-07-08","code":"P6310","name":"ống 63pn10pe80","unit":"M","weight_g":873.0,"group":"PE80","ca1":2300.0,"ca2":2300.0,"ca3":1200.0,"ca1_weight_g":2007900.0,"ca2_weight_g":2007900.0,"ca3_weight_g":1047600.0,"note":""},{"date":"2026-07-03","code":"P638","name":"ống 63pn8pe80","unit":"M","weight_g":721.0,"group":"PE80","ca1":0.0,"ca2":750.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":540750.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-03","code":"P638","name":"ống 63pn8pe80","unit":"M","weight_g":721.0,"group":"PE80","ca1":0.0,"ca2":0.0,"ca3":1150.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":829150.0,"note":"K3: Phè chỉ tháo đầu vệ sinh nắp lại(Từ 22:45 đến 02:15)"},{"date":"2026-07-04","code":"P638","name":"ống 63pn8pe80","unit":"M","weight_g":721.0,"group":"PE80","ca1":2450.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":1766450.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-04","code":"P638","name":"ống 63pn8pe80","unit":"M","weight_g":721.0,"group":"PE80","ca1":0.0,"ca2":1950.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":1405950.0,"ca3_weight_g":0.0,"note":"K2: Thay đầu zoăng bích chuốt(Từ 19:55 đến 22:00)"},{"date":"2026-07-07","code":"P7510","name":"ống 75pn10pe80","unit":"M","weight_g":1240.0,"group":"PE80","ca1":1400.0,"ca2":1650.0,"ca3":0.0,"ca1_weight_g":1736000.0,"ca2_weight_g":2046000.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-07","code":"P7510","name":"ống 75pn10pe80","unit":"M","weight_g":1240.0,"group":"PE80","ca1":0.0,"ca2":0.0,"ca3":1100.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":1364000.0,"note":"K3: Thay zoăng bích chuốt(Từ 03:30 đến 04:30)"},{"date":"2026-07-11","code":"P758","name":"ống 75pn8pe80","unit":"M","weight_g":1020.0,"group":"PE80","ca1":1000.0,"ca2":1900.0,"ca3":300.0,"ca1_weight_g":1020000.0,"ca2_weight_g":1938000.0,"ca3_weight_g":306000.0,"note":""},{"date":"2026-07-17","code":"P9012.5","name":"ống 90pn12.5pe80","unit":"M","weight_g":2120.0,"group":"PE80","ca1":125.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":265000.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-17","code":"P9012.5","name":"ống 90pn12.5pe80","unit":"M","weight_g":2120.0,"group":"PE80","ca1":0.0,"ca2":0.0,"ca3":775.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":1643000.0,"note":"K3: Thay đầu zoăng bích chuốt(Từ 04:30 đến 06:00)"},{"date":"2026-07-17","code":"P9012.5","name":"ống 90pn12.5pe80","unit":"M","weight_g":2120.0,"group":"PE80","ca1":0.0,"ca2":375.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":795000.0,"ca3_weight_g":0.0,"note":"K2: sửa hút hạt(Từ 15:40 đến 16:00)"},{"date":"2026-07-22","code":"P906","name":"ống 90pn6pe80","unit":"M","weight_g":1340.0,"group":"PE80","ca1":0.0,"ca2":1550.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":2077000.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-22","code":"P906","name":"ống 90pn6pe80","unit":"M","weight_g":1340.0,"group":"PE80","ca1":0.0,"ca2":0.0,"ca3":1500.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":2010000.0,"note":"K3: Thay zoăng bích chuốt(Từ 05:45 đến 06:00)"},{"date":"2026-07-28","code":"PE12512.5","name":"Ống HDPE(PE100) DN 125 PN12.5 dài 6m","unit":"m","weight_g":3370.0,"group":"PE100","ca1":0.0,"ca2":318.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":1071660.0,"ca3_weight_g":0.0,"note":"K2: Chờ nhiệt, chuẩn bị điều kiện chạy máy(Từ 14:00 đến 15:30);\n\rK2: Thay đầu zoăng bích chuốt(Từ 21:10 đến 22:00)"},{"date":"2026-07-28","code":"PE12512.5","name":"Ống HDPE(PE100) DN 125 PN12.5 dài 6m","unit":"m","weight_g":3370.0,"group":"PE100","ca1":0.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":"K2: Nháy điện, đứt ống, dừng máy kiểm tra xử lý chạy lại máy(Từ 17:00 đến 17:30);\n\rK2: vệ sinh đầu chuốt(Từ 16:15 đến 16:30)"},{"date":"2026-07-01","code":"PE6310","name":"Ống HDPE(PE100) DN 63 PN10","unit":"m","weight_g":721.0,"group":"PE100","ca1":2400.0,"ca2":2450.0,"ca3":2500.0,"ca1_weight_g":1730400.0,"ca2_weight_g":1766450.0,"ca3_weight_g":1802500.0,"note":""},{"date":"2026-07-02","code":"PE6310","name":"Ống HDPE(PE100) DN 63 PN10","unit":"m","weight_g":721.0,"group":"PE100","ca1":2450.0,"ca2":2450.0,"ca3":2400.0,"ca1_weight_g":1766450.0,"ca2_weight_g":1766450.0,"ca3_weight_g":1730400.0,"note":""},{"date":"2026-07-03","code":"PE6310","name":"Ống HDPE(PE100) DN 63 PN10","unit":"m","weight_g":721.0,"group":"PE100","ca1":2400.0,"ca2":1850.0,"ca3":0.0,"ca1_weight_g":1730400.0,"ca2_weight_g":1333850.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-08","code":"PE6312.5","name":"Ống HDPE(PE100) DN 63 PN12.5","unit":"m","weight_g":873.0,"group":"PE100","ca1":0.0,"ca2":0.0,"ca3":1050.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":916650.0,"note":""},{"date":"2026-07-09","code":"PE6312.5","name":"Ống HDPE(PE100) DN 63 PN12.5","unit":"m","weight_g":873.0,"group":"PE100","ca1":2350.0,"ca2":2350.0,"ca3":2300.0,"ca1_weight_g":2051550.0,"ca2_weight_g":2051550.0,"ca3_weight_g":2007900.0,"note":""},{"date":"2026-07-10","code":"PE6312.5","name":"Ống HDPE(PE100) DN 63 PN12.5","unit":"m","weight_g":873.0,"group":"PE100","ca1":2350.0,"ca2":0.0,"ca3":2300.0,"ca1_weight_g":2051550.0,"ca2_weight_g":0.0,"ca3_weight_g":2007900.0,"note":""},{"date":"2026-07-10","code":"PE6312.5","name":"Ống HDPE(PE100) DN 63 PN12.5","unit":"m","weight_g":873.0,"group":"PE100","ca1":0.0,"ca2":2150.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":1876950.0,"ca3_weight_g":0.0,"note":"K2: vệ sinh đầu chuốt(Từ 19:00 đến 19:15)"},{"date":"2026-07-11","code":"PE6312.5","name":"Ống HDPE(PE100) DN 63 PN12.5","unit":"m","weight_g":873.0,"group":"PE100","ca1":350.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":305550.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":"K1: Thay đầu zoăng bích chuốt(Từ 07:30 đến 10:00)"},{"date":"2026-07-11","code":"PE7510","name":"Ống HDPE(PE100) DN 75 PN10","unit":"m","weight_g":1020.0,"group":"PE100","ca1":0.0,"ca2":0.0,"ca3":1700.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":1734000.0,"note":""},{"date":"2026-07-12","code":"PE7510","name":"Ống HDPE(PE100) DN 75 PN10","unit":"m","weight_g":1020.0,"group":"PE100","ca1":2000.0,"ca2":2050.0,"ca3":2050.0,"ca1_weight_g":2040000.0,"ca2_weight_g":2091000.0,"ca3_weight_g":2091000.0,"note":""},{"date":"2026-07-13","code":"PE7510","name":"Ống HDPE(PE100) DN 75 PN10","unit":"m","weight_g":1020.0,"group":"PE100","ca1":1400.0,"ca2":850.0,"ca3":0.0,"ca1_weight_g":1428000.0,"ca2_weight_g":867000.0,"ca3_weight_g":0.0,"note":"K1: chỉ nhòe. Tháo đầu vệ sinh vành chỉ, căn lại (Từ 07:30 đến 09:30);\n\rK2: Thay zoăng bích chuốt(Từ 17:00 đến 17:35)"},{"date":"2026-07-04","code":"PE7512.5","name":"Ống HDPE(PE100) DN 75 PN12.5","unit":"m","weight_g":1240.0,"group":"PE100","ca1":0.0,"ca2":0.0,"ca3":1150.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":1426000.0,"note":"K3: Chờ nhiệt, chuẩn bị điều kiện chạy máy(Từ 22:00 đến 23:15)"},{"date":"2026-07-05","code":"PE7512.5","name":"Ống HDPE(PE100) DN 75 PN12.5","unit":"m","weight_g":1240.0,"group":"PE100","ca1":150.0,"ca2":1450.0,"ca3":900.0,"ca1_weight_g":186000.0,"ca2_weight_g":1798000.0,"ca3_weight_g":1116000.0,"note":"K1: động cơ chính bắt tia lửa(Từ 06:50 đến 14:00);\n\rK2: Tiếp tục sửa động cơ chính (Từ 14:00 đến 14:20);\n\rK3: Dán lại zoăng làm kín (Từ 03:30 đến 05:30)"},{"date":"2026-07-06","code":"PE7512.5","name":"Ống HDPE(PE100) DN 75 PN12.5","unit":"m","weight_g":1240.0,"group":"PE100","ca1":0.0,"ca2":1650.0,"ca3":1600.0,"ca1_weight_g":0.0,"ca2_weight_g":2046000.0,"ca3_weight_g":1984000.0,"note":""},{"date":"2026-07-06","code":"PE7512.5","name":"Ống HDPE(PE100) DN 75 PN12.5","unit":"m","weight_g":1240.0,"group":"PE100","ca1":850.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":1054000.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":"K1: Hỏng bơm nước (Từ 06:00 đến 09:00)"},{"date":"2026-07-07","code":"PE7512.5","name":"Ống HDPE(PE100) DN 75 PN12.5","unit":"m","weight_g":1240.0,"group":"PE100","ca1":300.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":372000.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-24","code":"PE7512.5","name":"Ống HDPE(PE100) DN 75 PN12.5","unit":"m","weight_g":1240.0,"group":"PE100","ca1":0.0,"ca2":1650.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":2046000.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-24","code":"PE7512.5","name":"Ống HDPE(PE100) DN 75 PN12.5","unit":"m","weight_g":1240.0,"group":"PE100","ca1":1200.0,"ca2":0.0,"ca3":1200.0,"ca1_weight_g":1488000.0,"ca2_weight_g":0.0,"ca3_weight_g":1488000.0,"note":"K1: Thay zoăng bích chuốt(Từ 06:00 đến 06:30);\n\rK3: Thay zoăng bích chuốt(Từ 03:15 đến 04:00)"},{"date":"2026-07-28","code":"PE7512.56","name":"Ống HDPE(PE100) DN 75 PN12.5 dài 6m","unit":"m","weight_g":1240.0,"group":"PE100","ca1":318.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":394320.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":"K1: Thay đầu zoăng bích chuốt(Từ 13:00 đến 14:00)"},{"date":"2026-07-28","code":"PE7516","name":"Ống HDPE(PE100) DN 75 PN16","unit":"m","weight_g":1470.0,"group":"PE100","ca1":0.0,"ca2":0.0,"ca3":775.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":1139250.0,"note":"K3: Chờ nhiệt, chuẩn bị điều kiện chạy máy(Từ 22:00 đến 23:30)"},{"date":"2026-07-29","code":"PE7516","name":"Ống HDPE(PE100) DN 75 PN16","unit":"m","weight_g":1470.0,"group":"PE100","ca1":1275.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":1874250.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-29","code":"PE75166","name":"Ống HDPE(PE100) DN 75 PN16 dài 6m","unit":"m","weight_g":1470.0,"group":"PE100","ca1":90.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":132300.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-29","code":"PE75166","name":"Ống HDPE(PE100) DN 75 PN16 dài 6m","unit":"m","weight_g":1470.0,"group":"PE100","ca1":0.0,"ca2":120.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":176400.0,"ca3_weight_g":0.0,"note":"K2: Thay đầu zoăng bích chuốt(Từ 15:20 đến 18:00)"},{"date":"2026-07-23","code":"PE758","name":"Ống HDPE(PE100) DN 75 PN8","unit":"m","weight_g":830.0,"group":"PE100","ca1":0.0,"ca2":1200.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":996000.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-23","code":"PE758","name":"Ống HDPE(PE100) DN 75 PN8","unit":"m","weight_g":830.0,"group":"PE100","ca1":1750.0,"ca2":0.0,"ca3":1100.0,"ca1_weight_g":1452500.0,"ca2_weight_g":0.0,"ca3_weight_g":913000.0,"note":"K1: Chờ nhiệt, chuẩn bị điều kiện chạy máy(Từ 06:00 đến 07:00);\n\rK3: Thay đầu zoăng bích chuốt(Từ 02:15 đến 06:00)"},{"date":"2026-07-13","code":"PE9010","name":"Ống HDPE(PE100) DN 90 PN10","unit":"m","weight_g":1460.0,"group":"PE100","ca1":0.0,"ca2":625.0,"ca3":1400.0,"ca1_weight_g":0.0,"ca2_weight_g":912500.0,"ca3_weight_g":2044000.0,"note":""},{"date":"2026-07-14","code":"PE9010","name":"Ống HDPE(PE100) DN 90 PN10","unit":"m","weight_g":1460.0,"group":"PE100","ca1":1375.0,"ca2":1400.0,"ca3":1400.0,"ca1_weight_g":2007500.0,"ca2_weight_g":2044000.0,"ca3_weight_g":2044000.0,"note":"K1: NULL"},{"date":"2026-07-15","code":"PE9010","name":"Ống HDPE(PE100) DN 90 PN10","unit":"m","weight_g":1460.0,"group":"PE100","ca1":1375.0,"ca2":1400.0,"ca3":1400.0,"ca1_weight_g":2007500.0,"ca2_weight_g":2044000.0,"ca3_weight_g":2044000.0,"note":""},{"date":"2026-07-16","code":"PE9010","name":"Ống HDPE(PE100) DN 90 PN10","unit":"m","weight_g":1460.0,"group":"PE100","ca1":1400.0,"ca2":1425.0,"ca3":1400.0,"ca1_weight_g":2044000.0,"ca2_weight_g":2080500.0,"ca3_weight_g":2044000.0,"note":""},{"date":"2026-07-17","code":"PE9010","name":"Ống HDPE(PE100) DN 90 PN10","unit":"m","weight_g":1460.0,"group":"PE100","ca1":450.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":657000.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":"K1: Thay đầu zoăng bích chuốt(Từ 08:45 đến 10:30)"},{"date":"2026-07-29","code":"PE9010","name":"Ống HDPE(PE100) DN 90 PN10","unit":"m","weight_g":1460.0,"group":"PE100","ca1":0.0,"ca2":475.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":693500.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-29","code":"PE9010","name":"Ống HDPE(PE100) DN 90 PN10","unit":"m","weight_g":1460.0,"group":"PE100","ca1":0.0,"ca2":0.0,"ca3":1400.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":2044000.0,"note":"K3: Hết kế hoạch(Từ 05:30 đến 06:00)"},{"date":"2026-07-24","code":"PE9012.5","name":"Ống HDPE(PE100) DN 90 PN12.5","unit":"m","weight_g":1770.0,"group":"PE100","ca1":0.0,"ca2":0.0,"ca3":150.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":265500.0,"note":""},{"date":"2026-07-25","code":"PE9012.5","name":"Ống HDPE(PE100) DN 90 PN12.5","unit":"m","weight_g":1770.0,"group":"PE100","ca1":1125.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":1991250.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":"K1: bảo dưỡng hệ thống điện(Từ 13:30 đến 14:00)"},{"date":"2026-07-27","code":"PE9012.5","name":"Ống HDPE(PE100) DN 90 PN12.5","unit":"m","weight_g":1770.0,"group":"PE100","ca1":0.0,"ca2":1150.0,"ca3":1150.0,"ca1_weight_g":0.0,"ca2_weight_g":2035500.0,"ca3_weight_g":2035500.0,"note":""},{"date":"2026-07-27","code":"PE9012.5","name":"Ống HDPE(PE100) DN 90 PN12.5","unit":"m","weight_g":1770.0,"group":"PE100","ca1":850.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":1504500.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":"K1: Chờ nhiệt, chuẩn bị điều kiện chạy máy(Từ 06:00 đến 06:35)"},{"date":"2026-07-27","code":"PE9012.5","name":"Ống HDPE(PE100) DN 90 PN12.5","unit":"m","weight_g":1770.0,"group":"PE100","ca1":0.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":"K1: Sửa cưa(Từ 08:45 đến 09:20)"},{"date":"2026-07-28","code":"PE9012.5","name":"Ống HDPE(PE100) DN 90 PN12.5","unit":"m","weight_g":1770.0,"group":"PE100","ca1":600.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":1062000.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":"K1: Thay zoăng bích chuốt(Từ 10:00 đến 10:40)"},{"date":"2026-07-17","code":"PE90162KH","name":"Ống HDPE(PE 100) DN 90 PN16 dài 2m (in theo YCKH )","unit":"m","weight_g":2120.0,"group":"PE100","ca1":0.0,"ca2":2.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":4240.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-17","code":"PE9016KH","name":"Ống HDPE(PE 100) DN 90 PN16 (in theo YCKH )","unit":"m","weight_g":2120.0,"group":"PE100","ca1":0.0,"ca2":175.0,"ca3":0.0,"ca1_weight_g":0.0,"ca2_weight_g":371000.0,"ca3_weight_g":0.0,"note":""},{"date":"2026-07-18","code":"PE908","name":"Ống HDPE(PE100) DN 90 PN8","unit":"m","weight_g":1340.0,"group":"PE100","ca1":0.0,"ca2":1575.0,"ca3":1525.0,"ca1_weight_g":0.0,"ca2_weight_g":2110500.0,"ca3_weight_g":2043500.0,"note":""},{"date":"2026-07-18","code":"PE908","name":"Ống HDPE(PE100) DN 90 PN8","unit":"m","weight_g":1340.0,"group":"PE100","ca1":1200.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":1608000.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":"K1: Chờ nhiệt, chuẩn bị điều kiện chạy máy(Từ 06:00 đến 06:45)"},{"date":"2026-07-19","code":"PE908","name":"Ống HDPE(PE100) DN 90 PN8","unit":"m","weight_g":1340.0,"group":"PE100","ca1":1525.0,"ca2":1550.0,"ca3":1575.0,"ca1_weight_g":2043500.0,"ca2_weight_g":2077000.0,"ca3_weight_g":2110500.0,"note":""},{"date":"2026-07-20","code":"PE908","name":"Ống HDPE(PE100) DN 90 PN8","unit":"m","weight_g":1340.0,"group":"PE100","ca1":1550.0,"ca2":1550.0,"ca3":1550.0,"ca1_weight_g":2077000.0,"ca2_weight_g":2077000.0,"ca3_weight_g":2077000.0,"note":""},{"date":"2026-07-21","code":"PE908","name":"Ống HDPE(PE100) DN 90 PN8","unit":"m","weight_g":1340.0,"group":"PE100","ca1":0.0,"ca2":0.0,"ca3":1550.0,"ca1_weight_g":0.0,"ca2_weight_g":0.0,"ca3_weight_g":2077000.0,"note":""},{"date":"2026-07-21","code":"PE908","name":"Ống HDPE(PE100) DN 90 PN8","unit":"m","weight_g":1340.0,"group":"PE100","ca1":1425.0,"ca2":1375.0,"ca3":0.0,"ca1_weight_g":1909500.0,"ca2_weight_g":1842500.0,"ca3_weight_g":0.0,"note":"K1: Nháy điện, đứt ống, dừng máy kiểm tra xử lý chạy lại máy(Từ 13:35 đến 14:00);\n\rK2: Nháy điện, đứt ống, dừng máy kiểm tra xử lý chạy lại máy(Từ 14:00 đến 14:10)"},{"date":"2026-07-22","code":"PE908","name":"Ống HDPE(PE100) DN 90 PN8","unit":"m","weight_g":1340.0,"group":"PE100","ca1":1525.0,"ca2":0.0,"ca3":0.0,"ca1_weight_g":2043500.0,"ca2_weight_g":0.0,"ca3_weight_g":0.0,"note":""}]
JSON;

    public function run(): void
    {
        if (app()->environment('production')
            && !filter_var(env('NTP_ALLOW_TEST_SEEDERS', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException(
                'Không được chạy seeder dữ liệu test trên production. '
                . 'Chỉ bật NTP_ALLOW_TEST_SEEDERS=true khi đã xác nhận đây là môi trường UAT.'
            );
        }

        $machine = Machine::query()
            ->where('code', self::MACHINE_CODE)
            ->first();

        if (!$machine) {
            throw new RuntimeException(
                'Không tìm thấy máy M62. Seeder này không tạo thêm máy.'
            );
        }

        $shifts = ProductionShift::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->limit(3)
            ->get();

        if ($shifts->count() < 3) {
            throw new RuntimeException(
                'Hệ thống phải có sẵn đủ 3 ca hoạt động. Seeder không tạo thêm ca.'
            );
        }

        $operator = User::query()
            ->where('username', 'test_vanhanh')
            ->first();

        $approver = User::query()
            ->where('username', 'test_quandoc')
            ->first();

        if (!$operator || !$approver) {
            throw new RuntimeException(
                'Chưa có tài khoản test vận hành/quản đốc. '
                . 'Hãy chạy NtpPlcTestUserSeeder trước.'
            );
        }

        $sourceRows = collect(json_decode(self::SOURCE_JSON, true, 512, JSON_THROW_ON_ERROR));
        $productMap = $this->prepareProductsAndStandards($machine, $sourceRows);

        $declarationGroups = $this->buildDeclarationGroups($sourceRows)
            ->map(function (Collection $group) use ($productMap): Collection {
                return $group->map(function (array $item) use ($productMap): array {
                    $product = $productMap[$item['code']]['product'];
                    $length = $this->productLengthMetres($product);

                    $item['source_metres'] = (float) $item['quantity'];
                    $item['actual_length_m'] = $length;
                    $item['quantity'] = round(
                        (float) $item['source_metres'] / $length,
                        4
                    );

                    return $item;
                });
            });

        $shiftTimeService = app(ShiftTimeService::class);
        $plcMetricService = app(PlcOeeMetricService::class);
        $oeeService = app(OeeCalculationService::class);

        $createdDeclarations = 0;
        $createdItems = 0;
        $oeeCalculated = 0;

        foreach ($declarationGroups as $key => $group) {
            [$date, $shiftNumber] = explode('|', $key);
            $shift = $shifts->values()[(int) $shiftNumber - 1];

            [$startedAt, $endedAt] = $shiftTimeService->range(
                $shift,
                Carbon::parse($date)
            );

            $firstItem = $group->first();
            $totalQuantity = (float) $group->sum('quantity');
            $totalGood = $totalQuantity;
            $totalReject = 0.0;
            $shiftMinutes = $startedAt->diffInMinutes($endedAt);

            $declaration = DB::transaction(function () use (
                $machine,
                $shift,
                $date,
                $startedAt,
                $endedAt,
                $firstItem,
                $totalQuantity,
                $totalGood,
                $totalReject,
                $shiftMinutes,
                $operator,
                $approver,
                $group,
                $productMap,
                &$createdItems
            ): ShiftProductionDeclaration {
                $declaration = ShiftProductionDeclaration::query()
                    ->withTrashed()
                    ->where('machine_id', $machine->id)
                    ->where('production_shift_id', $shift->id)
                    ->whereDate('production_date', $date)
                    ->where('record_scope', 0)
                    ->first();

                if ($declaration?->trashed()) {
                    $declaration->restore();
                }

                $declaration ??= new ShiftProductionDeclaration();

                $declaration->fill([
                    'machine_id' => $machine->id,
                    'production_shift_id' => $shift->id,
                    'production_date' => $date,
                    'record_scope' => 0,
                    'shift_started_at' => $startedAt,
                    'shift_ended_at' => $endedAt,
                    'product_code' => $firstItem['code'],
                    'product_name' => $group->count() > 1
                        ? $firstItem['name'] . ' và ' . ($group->count() - 1) . ' sản phẩm khác'
                        : $firstItem['name'],
                    'work_order' => 'UAT-M62-' . str_replace('-', '', $date)
                        . '-C' . $shift->display_order,
                    'good_quantity' => $totalGood,
                    'reject_quantity' => $totalReject,
                    'total_quantity' => $totalQuantity,
                    'planned_downtime_minutes' => 0,
                    'planned_production_minutes' => $shiftMinutes,
                    'plc_runtime_minutes' => null,
                    'unplanned_downtime_minutes' => null,
                    'ideal_rate_per_minute' => null,
                    'availability' => null,
                    'performance' => null,
                    'quality_rate' => $totalQuantity > 0 ? 1 : null,
                    'oee' => null,
                    'status' => ShiftProductionDeclaration::STATUS_APPROVED,
                    'operator_id' => $operator->id,
                    'submitted_at' => $endedAt->copy()->addMinutes(5),
                    'approved_by' => $approver->id,
                    'approved_at' => $endedAt->copy()->addMinutes(15),
                    'returned_by' => null,
                    'returned_at' => null,
                    'return_reason' => null,
                    'locked_by' => null,
                    'locked_at' => null,
                    'unlocked_by' => null,
                    'unlocked_at' => null,
                    'unlock_reason' => null,
                    'note' => 'Dữ liệu UAT M62 được tạo từ báo cáo Tình hình sản xuất ngày chi tiết tháng 07/2026.',
                    'calculation_note' => 'Dữ liệu UAT vừa được nạp; hệ thống sẽ thử tính OEE theo MD488/MD264.',
                    'deleted_by' => null,
                    'delete_reason' => null,
                ]);

                $declaration->deleted_at = null;
                $declaration->save();

                $declaration->items()->delete();

                foreach ($group->values() as $index => $sourceItem) {
                    $prepared = $productMap[$sourceItem['code']];
                    $product = $prepared['product'];
                    $standard = $prepared['standard'];
                    $quantity = (float) $sourceItem['quantity'];
                    $sourceMetres = (float) $sourceItem['source_metres'];
                    $actualLength = (float) $sourceItem['actual_length_m'];
                    $totalKg = round((float) $sourceItem['weight_g'] / 1000, 3);

                    ShiftProductionDeclarationItem::query()->create([
                        'shift_production_declaration_id' => $declaration->id,
                        'product_id' => $product->id,
                        'machine_product_standard_id' => $standard->id,
                        'product_code_snapshot' => $product->material_code,
                        'product_name_snapshot' => $product->material_name,
                        'unit_snapshot' => $product->unit ?: 'm',
                        'standard_weight_g_snapshot' => (float) (
                            $product->standard_weight_g
                            ?: $sourceItem['standard_weight_g']
                            ?: 0
                        ),
                        'default_length_m_snapshot' => 1,
                        /*
                         * Báo cáo Bravo lưu CA1/CA2/CA3 theo mét.
                         * Quy đổi về số cây/cuộn:
                         *
                         * số lượng = số mét nguồn / chiều dài mặc định sản phẩm.
                         *
                         * Trọng lượng vẫn giữ đúng theo cột CAx_TrLg của báo cáo.
                         */
                        'actual_length_m' => $actualLength,
                        'standard_rate_snapshot' => (float) $standard->standard_rate,
                        'rate_unit_snapshot' => $standard->rate_unit ?: 'kg/h',
                        'total_quantity' => $quantity,
                        'good_quantity' => $quantity,
                        'reject_quantity' => 0,
                        'scrap_quantity' => 0,
                        'converted_total_kg' => $totalKg,
                        'converted_good_kg' => $totalKg,
                        'converted_reject_kg' => 0,
                        'converted_scrap_kg' => 0,
                        'work_order' => 'UAT-M62-' . str_replace('-', '', $date)
                            . '-C' . $shift->display_order,
                        'note' => trim(
                            sprintf(
                                'Nguồn tháng 07/2026: %.4f m / %.4f m mỗi %s = %.4f %s.',
                                $sourceMetres,
                                $actualLength,
                                $product->unit ?: 'ĐVT',
                                $quantity,
                                $product->unit ?: 'ĐVT'
                            )
                            . ($sourceItem['note'] ? ' ' . $sourceItem['note'] : '')
                        ),
                        'display_order' => $index + 1,
                    ]);

                    $createdItems++;
                }

                return $declaration->fresh(['machine.plcs', 'shift', 'items']);
            });

            $createdDeclarations++;

            if (filter_var(
                env('NTP_TEST_SEED_CALCULATE_OEE', true),
                FILTER_VALIDATE_BOOL
            )) {
                try {
                    $metrics = $plcMetricService->collect($declaration);
                    $result = $oeeService->calculate($declaration, $metrics);

                    if ($result->oee !== null) {
                        $oeeCalculated++;
                    }
                } catch (Throwable $exception) {
                    $declaration->update([
                        'calculation_note' => 'Đã nạp dữ liệu UAT nhưng không tự tính được OEE: '
                            . $exception->getMessage(),
                    ]);

                    $this->command?->warn(
                        "Phiếu {$date} {$shift->name}: "
                        . $exception->getMessage()
                    );
                }
            }
        }

        $this->command?->newLine();
        $this->command?->info(
            "Đã nạp {$createdDeclarations} phiếu, {$createdItems} dòng sản phẩm cho M62."
        );
        $this->command?->info(
            "Có {$oeeCalculated} phiếu tính được OEE ngay từ lịch sử PLC hiện có."
        );
        $this->command?->line(
            'Sản phẩm/định mức đã có được giữ nguyên; chỉ tạo phần còn thiếu.'
        );
    }

    private function prepareProductsAndStandards(
        Machine $machine,
        Collection $sourceRows
    ): array {
        $map = [];

        foreach ($sourceRows->unique('code')->values() as $row) {
            $product = Product::withTrashed()
                ->where('material_code', $row['code'])
                ->first();

            if (!$product) {
                $product = Product::query()->create([
                    'material_code' => $row['code'],
                    'material_name' => $row['name'],
                    'unit' => $this->normalizeUnit($row['unit']),
                    'standard_weight_g' => (float) $row['weight_g'],
                    'default_length_m' => $this->inferLengthFromName(
                        (string) $row['name']
                    ),
                    'is_active' => true,
                    'notes' => trim(
                        'Tự tạo bởi M62JulyProductionTestSeeder.'
                        . ($row['group'] ? ' Nhóm nguồn: ' . $row['group'] . '.' : '')
                    ),
                ]);

                $this->command?->line(
                    "Tạo sản phẩm thiếu: {$row['code']} — {$row['name']}"
                );
            } elseif ($product->trashed()) {
                $product->restore();
            }

            $standard = MachineProductStandard::withTrashed()
                ->where('machine_id', $machine->id)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->whereNull('effective_from')
                        ->orWhereDate('effective_from', '<=', '2026-07-31');
                })
                ->where(function ($query): void {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', '2026-07-01');
                })
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first();

            if (!$standard) {
                $standard = MachineProductStandard::query()->create([
                    'machine_id' => $machine->id,
                    'product_id' => $product->id,
                    'standard_rate' => 1,
                    'rate_unit' => 'kg/h',
                    'effective_from' => '2026-07-01',
                    'effective_to' => null,
                    'is_active' => true,
                    'notes' => 'Định mức tạm = 1 kg/h để kiểm thử M62. Cần thay bằng định mức thực tế.',
                ]);

                $this->command?->line(
                    "Tạo định mức thiếu = 1 kg/h: {$row['code']}"
                );
            } elseif ($standard->trashed()) {
                $standard->restore();
            }

            $map[$row['code']] = [
                'product' => $product,
                'standard' => $standard,
            ];
        }

        return $map;
    }

    private function buildDeclarationGroups(Collection $sourceRows): Collection
    {
        $items = collect();

        foreach ($sourceRows as $row) {
            foreach ([1, 2, 3] as $shiftNumber) {
                $quantity = (float) $row['ca' . $shiftNumber];

                if ($quantity <= 0) {
                    continue;
                }

                $items->push([
                    'date' => $row['date'],
                    'shift_number' => $shiftNumber,
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'quantity' => $quantity,
                    'weight_g' => (float) $row['ca' . $shiftNumber . '_weight_g'],
                    'standard_weight_g' => (float) $row['weight_g'],
                    'note' => $row['note'],
                ]);
            }
        }

        return $items->groupBy(
            fn (array $item): string =>
                $item['date'] . '|' . $item['shift_number']
        )->sortKeys();
    }

    private function productLengthMetres(Product $product): float
    {
        $length = (float) $product->default_length_m;

        if ($length > 0) {
            return $length;
        }

        $inferred = $this->inferLengthFromName(
            (string) $product->material_name
        );

        $this->command?->warn(sprintf(
            'Sản phẩm %s chưa có default_length_m; tạm dùng %.4f m.',
            $product->material_code,
            $inferred
        ));

        return $inferred;
    }

    private function inferLengthFromName(string $name): float
    {
        if (preg_match(
            '/(?:dài|dai)\s*([0-9]+(?:[.,][0-9]+)?)\s*m\b/iu',
            $name,
            $matches
        )) {
            return max(
                (float) str_replace(',', '.', $matches[1]),
                0.0001
            );
        }

        /*
         * Không tự suy đoán cây hay cuộn khi tên không ghi chiều dài.
         * Dùng 1 m để dữ liệu vẫn nạp được và ghi cảnh báo.
         */
        return 1.0;
    }

    private function normalizeUnit(?string $unit): string
    {
        $unit = mb_strtolower(trim((string) $unit));

        return match ($unit) {
            'm', 'met', 'mét', 'meter', 'metre' => 'm',
            'kg', 'kilogram' => 'kg',
            default => $unit !== '' ? $unit : 'm',
        };
    }
}
