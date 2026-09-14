<?php
declare(strict_types=1);

namespace AIVANBAN\Services;

trait PhotoTeachingForms
{
    private function form02(array $course, array $assignments, array $teacherNames, array $moduleNames): string
    {
        $visible = [1 => true, 2 => true, 3 => true, 25 => true, 26 => true];
        foreach ($assignments as $r) { $w = (int) $this->v($r, 'tuan'); if ($w >= 1 && $w <= 26) $visible[$w] = true; }
        ksort($visible); $weeks = []; $last = 0;
        foreach (array_keys($visible) as $w) { if ($w > $last + 1) $weeks[] = null; $weeks[] = $w; $last = $w; }
        $nw = count($weeks);
        $heads = [
            [$this->pc('Số TT', 1, 3), $this->pc('HỌ VÀ TÊN GIÁO VIÊN', 1, 3), $this->pc('Tháng', 2), $this->pc('BỐ TRÍ GIẢNG DẠY', $nw), $this->pc('Số giờ giảng', 1, 3), $this->pc('Các nhiệm vụ khác', 2), $this->pc('Tổng số giờ giảng trong học kỳ', 1, 3), $this->pc('Giờ tiêu chuẩn theo quy định', 1, 3), $this->pc('SO SÁNH', 2)],
            [$this->pc('Tuần', 2), ...array_map(static fn ($w): string => $w === null ? '......' : (string) $w, $weeks), $this->pc('NỘI DUNG', 1, 2), $this->pc('Quy đổi thành giờ giảng', 1, 2), $this->pc('Giờ thừa', 1, 2), $this->pc('Giờ thiếu', 1, 2)],
            ['Môn học, Mô-đun', 'Lớp', ...array_fill(0, $nw, '')],
            ['1', '2', '3', '4', $this->pc('5', $nw), '6', '7', '8', '9', '10', '11', '12'],
        ];
        $rows = [];
        foreach ($assignments as $i => $r) {
            $hours = $this->v($r, 'sogio');
            $rows[] = [$i + 1, $teacherNames[$this->v($r, 'magiaovien')] ?? $this->v($r, 'hovaten'), $moduleNames[$this->v($r, 'mamonmodun')] ?? $this->v($r, 'tenmonmodun'), $this->v($r, 'malop'), ...array_map(fn ($w): string => $w !== null && $w === (int) $this->v($r, 'tuan') ? $hours : '', $weeks), $hours, $this->v($r, 'nhiemvukhac'), $this->v($r, 'quydoigiogiang'), $this->v($r, 'tonggiogianghocky'), $this->v($r, 'giotieuchuan'), $this->v($r, 'giothua'), $this->v($r, 'giothieu')];
        }
        $html = $this->pLabel(2) . $this->pOfficialHeader($course) . '<p><b>Khoa (tổ môn):</b> ' . $this->pValue($this->v($course, 'khoa')) . '</p><h1>KẾ HOẠCH GIÁO VIÊN</h1><p class="center">NĂM HỌC: ' . $this->pValue($this->v($course, 'namhoc')) . ' HỌC KỲ: ' . $this->pValue($this->v($course, 'hocky')) . '</p>';
        return $html . $this->pGrid([4, 16, 7, 7, ...array_fill(0, $nw, 26 / $nw), 6, 9, 6, 7, 6, 3, 3], $heads, $rows, 10, 6, 7.5) . $this->pSign('HIỆU TRƯỞNG/GIÁM ĐỐC', 'TRƯỞNG KHOA, BỘ MÔN');
    }

    private function form03(array $course, array $students, array $teachers, array $schedule, array $grades, array $attendance): string
    {
        $html = $this->pCover(3, 'SỔ LÊN LỚP', $course, ['Lớp' => $this->v($course, 'malop'), 'Trình độ' => $this->v($course, 'trinhdo'), 'Nghề' => $this->v($course, 'nghedaotao'), 'Khóa' => $this->v($course, 'tenkhoa')]);
        $contents = ['Danh sách giáo viên', 'Thời khóa biểu', 'Theo dõi ngày học tập', 'Bảng ghi tóm tắt nội dung', 'Bảng ghi điểm', 'Xếp loại kết quả rèn luyện', 'Tổng hợp kết quả học tập', 'Tổng hợp đánh giá cuối năm - cuối khóa', 'Kiểm tra tình hình dạy học', 'Hướng dẫn sử dụng'];
        $html .= $this->pPage(3, 'MỤC LỤC') . '<div class="usage"><ol>';
        foreach ($contents as $s) $html .= '<li style="margin:5mm 0">' . $s . '</li>';
        $html .= '</ol></div>';
        $rows = [];
        foreach ($teachers as $i => $r) $rows[] = [$i + 1, $this->v($r, 'hovaten'), $this->v($r, 'tenmonmodun') ?: $this->v($r, 'mamonmodun'), $this->v($r, 'sogiogiangday')];
        $html .= $this->pPage(3, 'DANH SÁCH GIÁO VIÊN GIẢNG DẠY') . $this->pGrid([7, 43, 32, 18], [['TT', 'HỌ VÀ TÊN GIÁO VIÊN', 'GIẢNG DẠY MÔN HỌC/MÔ-ĐUN', 'SỐ GIỜ GIẢNG DẠY']], $rows, 23, 6, 9);
        $html .= $this->pGrid([7,43,32,18], [['TT', 'GIÁO VIÊN CHỦ NHIỆM', '', '']], [['1', $this->v($course,'giaovienchunhiem'), '', ''], ['2','','',''], ['3','','','']], 0, 6, 9);
        $heads = [[], []];
        foreach (range(2,6) as $day) { $heads[0][] = $this->pc('THỨ ' . $day,2); $heads[1][] = 'Nội dung'; $heads[1][] = 'Thời gian'; }
        $rows = [];
        for ($i=0;$i<8;$i++) { $r=[]; foreach(range(2,6) as $d) { $r[]='';$r[]="Từ ........\nĐến ......"; } $rows[]=$r; }
        $html .= $this->pPage(3, 'THỜI KHÓA BIỂU','L') . '<p class="center"><i>(Thực hiện từ ngày .... tháng .... năm .... đến ngày .... tháng .... năm ....)</i></p>' . $this->pGrid(array_fill(0,10,10),$heads,$rows,0,12,9) . $this->pSign('', 'TRƯỞNG PHÒNG ĐÀO TẠO (GIÁM ĐỐC)');
        $html .= $this->pAttendance(3, $students, $attendance);
        $heads = [[$this->pc('NGÀY LÊN LỚP',1,2),$this->pc('SỐ GIỜ',3),$this->pc('TÓM TẮT NỘI DUNG BÀI DẠY, KIỂM TRA',1,2),$this->pc('SỐ HỌC SINH/SINH VIÊN VẮNG MẶT',1,2),$this->pc('CHỮ KÝ GIÁO VIÊN',1,2)],['Lý thuyết','Thực hành','Kiểm tra']];
        $journal=[];
        foreach($schedule as $r) if($this->v($r,'ngaylenlop')!=='') $journal[]=[$this->date($this->v($r,'ngaylenlop')),$this->v($r,'giolythuyet'),$this->v($r,'giothuchanh'),$this->v($r,'giokiemtra'),$this->v($r,'noidung'),$this->v($r,'sosinhvienvang'),''];
        $html .= $this->pPage(3,'BẢNG GHI TÓM TẮT NỘI DUNG') . '<p>HỌ VÀ TÊN GIÁO VIÊN: ........................................<br>MÔN HỌC/MÔ-ĐUN: ................................................</p>' . $this->pGrid([12,8,8,8,36,15,13],$heads,$journal,32,5.5,8);
        $html .= $this->pGradeSheets(3,$students,$grades);
        $head=[[$this->pc('SỐ TT',1,2),$this->pc('HỌ VÀ TÊN',1,2),$this->pc('NHẬN XÉT TÓM TẮT',1,2),$this->pc('XẾP LOẠI',7)],['Xuất sắc','Tốt','Khá','Trung bình khá','Trung bình','Yếu','Kém']];
        $rows=[];foreach($students as $i=>$s) $rows[]=[$i+1,$s['full_name'],'',...array_fill(0,7,'')];
        $html.=$this->pPage(3,'XẾP LOẠI KẾT QUẢ RÈN LUYỆN').'<p class="center">HỌC KỲ: ........................</p>'.$this->pGrid([5,20,24,...array_fill(0,7,51/7)],$head,$rows,30,6,8);
        $modules=[];foreach($grades as $g){$k=$this->v($g,'mamonmodun');if($k!=='')$modules[$k]=$this->v($g,'tenmonmodun')?:$k;}
        $moduleChunks=array_chunk($modules?:[''=>''],3,true);
        foreach($moduleChunks as $chunk){
            $codes=array_keys($chunk);$names=array_values($chunk);while(count($codes)<3){$codes[]='';$names[]='';}
            $head=[[$this->pc('SỐ TT',1,3),$this->pc('HỌ VÀ TÊN',1,3),$this->pc('KẾT QUẢ HỌC TẬP MÔN HỌC/MÔ-ĐUN',3),$this->pc('MÔN HỌC/MÔ-ĐUN PHẢI HỌC LẠI',1,3)],array_map(static fn($n)=>'Tên MH/MĐ: '.$n."\nHệ số: ........",$names),array_fill(0,3,'ĐIỂM TỔNG KẾT')];
            $rows=[];foreach($students as $i=>$s){$r=[$i+1,$s['full_name']];foreach($codes as $code){$matches=array_values(array_filter($grades,fn($g)=>$this->v($g,'mssv')===$s['student_code']&&$this->v($g,'mamonmodun')===$code));$r[]=$this->v($matches[0]??[],'diemtongket');}$r[]='';$rows[]=$r;}
            $html.=$this->pPage(3,'TỔNG HỢP KẾT QUẢ HỌC TẬP').$this->pGrid([6,19,19,19,19,18],$head,$rows,30,6,8);
        }
        $head=[[$this->pc('SỐ TT',1,2),$this->pc('HỌ VÀ TÊN',1,2),$this->pc('SỐ GIỜ NGHỈ HỌC',3),$this->pc('ĐIỂM TRUNG BÌNH CHUNG',1,2),$this->pc('KẾT QUẢ RÈN LUYỆN',1,2),$this->pc('MÔN HỌC/MÔ-ĐUN PHẢI HỌC LẠI',1,2),$this->pc('GHI CHÚ',1,2)],['Tổng số','Có phép','Không phép']];
        $rows=[];foreach($students as $i=>$s){$a=array_values(array_filter($attendance,fn($r)=>$this->v($r,'mssv')===$s['student_code']));$p=$this->sumAttendance($a,'sogionghicophep');$k=$this->sumAttendance($a,'sogionghikhongphep');$rows[]=[$i+1,$s['full_name'],$a===[]?'':(string)((float)$p+(float)$k),$p,$k,'','','',''];}
        $html.=$this->pPage(3,'TỔNG HỢP ĐÁNH GIÁ CUỐI NĂM HỌC/CUỐI KHÓA').$this->pGrid([5,19,7,7,8,12,12,18,12],$head,$rows,30,6,8);
        // The supplied photo set has no separate inspection table page.
        return $html.$this->pPage(3,'HƯỚNG DẪN SỬ DỤNG').$this->pUsage(3);
    }

    private function pGradeSheets(int $number,array $students,array $grades):string
    {
        $groups=$this->groupBy($grades,'mamonmodun');if($groups===[])$groups=[''=>[]];$html='';
        foreach($groups as $code=>$items){
            $byStudent=$this->indexRecords($items,'mssv');
            $title=$number===3?'BẢNG GHI ĐIỂM':'KẾT QUẢ HỌC TẬP';
            $html.=$this->pPage($number,$title).'<p class="center">MÔN HỌC/MÔ-ĐUN: '.$this->pValue($this->v($items[0]??[],'tenmonmodun')?:(string)$code).'</p>';
            if($number===3){
                $heads=[[$this->pc('TT',1,4),$this->pc('HỌ VÀ TÊN HỌC SINH',1,4),$this->pc('NGÀY KIỂM TRA',8),$this->pc('Điểm tổng kết',1,4),$this->pc('GHI CHÚ',1,4)],array_fill(0,8,''),[$this->pc('Điểm kiểm tra định kỳ Môn học/Mô-đun',6,2),$this->pc('Điểm kiểm tra kết thúc Môn học/Mô-đun',2)],['Lần 1','Lần 2']];
                $rows=[];foreach($students as $i=>$s){$g=$byStudent[$s['student_code']]??[];$r=[$i+1,$s['full_name']];foreach(range(1,6)as$d)$r[]=$this->v($g,'diemdinhky'.$d);$rows[]=[...$r,$this->v($g,'diemketthuclan1'),$this->v($g,'diemketthuclan2'),$this->v($g,'diemtongket'),$this->v($g,'ghichu')];}
                $html.=$this->pGrid([6,21,...array_fill(0,6,5.5),8,8,8,16],$heads,$rows,28,6,8);
            }else{
                $heads=[[$this->pc('SỐ TT',1,2),$this->pc('HỌ VÀ TÊN HỌC SINH/SINH VIÊN',1,2),$this->pc('KIỂM TRA ĐỊNH KỲ MH/MĐ',1,2),$this->pc('KIỂM TRA KẾT THÚC MH/MĐ',2),$this->pc('ĐIỂM TỔNG KẾT',1,2)],['LẦN 1','LẦN 2']];
                $rows=[];foreach($students as$i=>$s){$g=$byStudent[$s['student_code']]??[];$periodic=[];foreach(range(1,6)as$d){$v=$this->v($g,'diemdinhky'.$d);if($v!=='')$periodic[]=$v;}$rows[]=[$i+1,$s['full_name'],implode('     ',$periodic),$this->v($g,'diemketthuclan1'),$this->v($g,'diemketthuclan2'),$this->v($g,'diemtongket')];}
                $html.=$this->pGrid([7,22,40,9,9,13],$heads,$rows,30,6.3,9);
            }
        }return $html;
    }

    private function pAttendance(int $number,array $students,array $attendance):string
    {
        $months=[];
        foreach($attendance as$r){$m=$this->v($r,'thang');$date=$this->date($this->v($r,'ngay'));if(preg_match('~^\d{2}/(\d{2}/\d{4})$~',$date,$x))$m=$x[1];if($m!=='')$months[$m][]=$r;}
        if($months===[])$months=[''=>[]];$html='';
        foreach($months as$month=>$items){
            $days=$number===3?[1,2,3,29,30,31]:[1,2,3,4,5,27,28,29,30,31];
            foreach($items as$r){$date=$this->date($this->v($r,'ngay'));if(preg_match('~^(\d{2})/\d{2}/\d{4}$~',$date,$m))$days[]=(int)$m[1];}
            $days=array_values(array_unique($days));sort($days);$cols=[];$prev=0;foreach($days as$d){if($d>$prev+1)$cols[]=null;$cols[]=$d;$prev=$d;}$nd=count($cols);
            $head=[[$this->pc('SỐ TT',1,2),$this->pc('HỌ VÀ TÊN HỌC SINH/SINH VIÊN',1,2),$this->pc('NGÀY',$nd)]];
            if($number===3)$head[0]=[...$head[0],$this->pc('Số giờ nghỉ có phép',1,2),$this->pc('Số giờ nghỉ không phép',1,2),$this->pc('GHI CHÚ',1,2)];else$head[0][]=$this->pc('TỔNG SỐ',1,2);
            $head[]=array_map(static fn($d)=>$d===null?'......':(string)$d,$cols);
            $rows=[];
            foreach($students as$i=>$s){$a=array_values(array_filter($items,fn($r)=>$this->v($r,'mssv')===$s['student_code']));$r=[$i+1,$s['full_name']];foreach($cols as$d){$marks=[];foreach($a as$e){if($d!==null&&(int)substr($this->date($this->v($e,'ngay')),0,2)===$d){$cp=$this->v($e,'sogionghicophep');$kp=$this->v($e,'sogionghikhongphep');if($number===3){if((float)$cp>0)$marks[]='P '.$cp;if((float)$kp>0)$marks[]='K '.$kp;}elseif($cp!==''||$kp!=='')$marks[]=(string)((float)$cp+(float)$kp);}}$r[]=implode('; ',$marks);}
                $p=$this->sumAttendance($a,'sogionghicophep');$k=$this->sumAttendance($a,'sogionghikhongphep');$rows[]=$number===3?[...$r,$p,$k,'']:[...$r,$a===[]?'':(string)((float)$p+(float)$k)];
            }
            $widths=$number===3?[6,24,...array_fill(0,$nd,46/$nd),8,8,8]:[6,22,...array_fill(0,$nd,62/$nd),10];
            $html.=$this->pPage($number,$number===3?'THEO DÕI NGÀY HỌC TẬP':'SỐ GIỜ NGHỈ TRONG THÁNG').'<p style="text-align:right">Tháng: '.$this->pValue((string)$month).'</p>';
            while(count($rows)<28)$rows[]=array_fill(0,count($widths),'');
            if($number===3){$rows[]=['',$this->pc('Cộng - Không phép'),...array_fill(0,$nd+3,'')];$rows[]=['','Cộng - Có phép',...array_fill(0,$nd+3,'')];}
            $html.=$this->pGrid($widths,$head,$rows,0,6,8);
        }return $html;
    }

    private function form04(array $course,array $students,array $assignments,array $teacherNames,array $moduleNames,array $grades,array $attendance):string
    {
        $first=$assignments[0]??[];$teacher=$teacherNames[$this->v($first,'magiaovien')]??'';$module=$moduleNames[$this->v($first,'mamonmodun')]??'';
        $html=$this->pCover(4,'SỔ TAY GIÁO VIÊN',$course,['Môn học/Mô-đun'=>$module,'Lớp'=>$this->v($course,'malop'),'Khóa'=>$this->v($course,'tenkhoa'),'Họ và tên giáo viên'=>$teacher]);
        $html.=$this->pPage(4,'THÔNG TIN VỀ LỚP HỌC/KHÓA').'<div class="usage"><p>1. Nghề đào tạo: '.$this->pValue($this->v($course,'nghedaotao')).'</p><p>2. Trình độ đào tạo nghề: '.$this->pValue($this->v($course,'trinhdo')).'</p><p>3. Trình độ đầu vào và hình thức đánh giá đầu vào:</p>'.$this->pLines(2,$this->v($course,'trinhdodauvao')).'<p>4. Quyết định thành lập lớp học: '.$this->pValue($this->v($course,'quyetdinhthanhlaplop')).'</p><p>5. Tổ chức lớp học</p><p>a) Sĩ số lớp học: '.$this->pValue($this->v($course,'siso')).'</p><p>b) Bộ máy quản lý lớp:</p><p>- Giáo viên chủ nhiệm: '.$this->pValue($this->v($course,'giaovienchunhiem')).'</p><p>- Lớp trưởng: '.$this->pValue($this->v($course,'loptruong')).'</p><p>- Lớp phó và các tổ trưởng:</p>'.$this->pLines(4,$this->v($course,'lopphoto truong')).'<p>c) Phương thức tổ chức đào tạo:</p>'.$this->pLines(7,$this->v($course,'phuongthuctochucdaotao')).'</div>';
        $html.=$this->pGradeSheets(4,$students,$grades).$this->pAttendance(4,$students,$attendance);
        $monthValues=[];
        foreach($attendance as $ar){
            $month=$this->v($ar,'thang');
            if($month===''){$day=$this->date($this->v($ar,'ngay'));if(preg_match('~^\d{2}/(\d{2}/\d{4})$~',$day,$m))$month=$m[1];}
            if($month!=='')$monthValues[$month]=true;
        }
        $months=array_slice(array_keys($monthValues),0,11);
        while(count($months)<11)$months[]='';
        $head=[[$this->pc('SỐ TT',1,2),$this->pc('HỌ VÀ TÊN HỌC SINH/SINH VIÊN',1,2),$this->pc('THÁNG',11),$this->pc('TỔNG SỐ',1,2)],$months];
        $rows=[];foreach($students as$i=>$s){$values=[];$total=0;foreach($months as$month){$sum=0;if($month!=='')foreach($attendance as$ar){$arMonth=$this->v($ar,'thang');if($arMonth==='' ){$day=$this->date($this->v($ar,'ngay'));if(preg_match('~^\d{2}/(\d{2}/\d{4})$~',$day,$m))$arMonth=$m[1];}if($arMonth===$month&&$this->v($ar,'mssv')===$s['student_code'])$sum+=(float)$this->v($ar,'sogionghicophep')+(float)$this->v($ar,'sogionghikhongphep');}$values[]=$sum>0?(string)$sum:'';$total+=$sum;}$rows[]=[$i+1,$s['full_name'],...$values,$total>0?(string)$total:''];}
        $html.=$this->pPage(4,'TỔNG HỢP SỐ GIỜ NGHỈ CHO MÔN HỌC/MÔ-ĐUN').$this->pGrid([6,22,...array_fill(0,11,62/11),10],$head,$rows,32,6,8);
        $html.=$this->pPage(4,'QUẢN LÝ HỌC SINH/SINH VIÊN CÁ BIỆT').'<p class="center"><i>(Tên học sinh, đặc điểm, hình thức quản lý giáo dục, đánh giá phát triển)</i></p>'.$this->pLines(32);
        $html.=$this->pPage(4,'ĐÁNH GIÁ QUÁ TRÌNH GIẢNG DẠY MÔN HỌC/MÔ-ĐUN').'<p class="center"><i>(Đánh giá chung quá trình tổ chức đào tạo, quản lý lớp học và kết quả học tập của lớp học, kinh nghiệm giảng dạy môn học/mô-đun)</i></p>'.$this->pLines(28);
        return$html.$this->pPage(4,'HƯỚNG DẪN SỬ DỤNG').$this->pUsage(4);
    }

    private function lessonBook(array $course,int $number,string $kind,array $assignments,array $teacherNames,array $moduleNames):string
    {
        $first=$assignments[0]??[];
        $html=$this->pCover($number,'SỔ GIÁO ÁN '.$kind,$course,['Môn học/Mô-đun'=>$moduleNames[$this->v($first,'mamonmodun')]??'','Lớp'=>$this->v($first,'malop')?:$this->v($course,'malop'),'Họ và tên giáo viên'=>$teacherNames[$this->v($first,'magiaovien')]??'']);
        foreach($assignments?:[[]] as$r){
            $html.=$this->pPage($number).'<table class="lesson-meta"><tr><td><b>GIÁO ÁN SỐ: '.$this->pValue($this->v($r,'sogiaoan')).'</b></td><td>Thời gian thực hiện: '.$this->pValue($this->v($r,'sogio')).'<br>'.($number===5?'Tên chương: ':($number===6?'Bài học trước: ':'Tên bài học trước: ')).$this->pValue($this->v($r,$number===5?'tenchuong':'baihoc truoc')).'<br>Thực hiện '.($number===5?'ngày: '.$this->pValue($this->date($this->v($r,'ngayday'))):'từ ngày '.$this->pValue($this->date($this->v($r,'tungay'))).' đến ngày '.$this->pValue($this->date($this->v($r,'denngay')))).'</td></tr></table>';
            $html.='<h3>TÊN BÀI: '.$this->pValue($this->v($r,'tenbai')).'</h3><h3>MỤC TIÊU CỦA BÀI:</h3><p>Sau khi học xong bài này người học có khả năng:</p>'.$this->pLines($number===5?6:4,$this->v($r,'muctieu'),5).'<h3>ĐỒ DÙNG VÀ PHƯƠNG TIỆN DẠY HỌC</h3>'.$this->pLines($number===5?5:4,$this->v($r,'dodungphuongtien'),5);
            if($number!==5)$html.='<h3>HÌNH THỨC TỔ CHỨC DẠY HỌC:</h3>'.$this->pLines(3,$this->v($r,'hinhthuctochuc'),5);
            $html.='<h3>I. ỔN ĐỊNH LỚP HỌC: <span style="font-weight:normal">Thời gian: ............</span></h3>'.$this->pLines(2,$this->v($r,'ondinhlop'),5).'<h3>II. THỰC HIỆN BÀI HỌC</h3>';
            $head=[[$this->pc('TT',1,2),$this->pc('NỘI DUNG',1,2),$this->pc('HOẠT ĐỘNG DẠY HỌC',2),$this->pc('THỜI GIAN',1,2)],['HOẠT ĐỘNG CỦA GIÁO VIÊN','HOẠT ĐỘNG CỦA HỌC SINH']];
            $intro='<b>Dẫn nhập</b><br><i>(Gợi mở, trao đổi phương pháp học, tạo tâm thế tích cực của người học....)</i>';
            $html.='<div class="lesson-table">'.$this->pGrid([6,31,26,26,11],$head,[['1',$this->pc($intro,1,1,true),$this->v($r,'dannhapgiaovien'),$this->v($r,'dannhaphocsinh'),$this->v($r,'thoigiandannhap')]],0,25,9).'</div>';
            $html.='<pagebreak orientation="P" />';
            if($number===5){
                $html.=$this->pLessonRow('2','Giảng bài mới','(Đề cương bài giảng)',120,$r,'giangbaimoi');
                $html.=$this->pLessonRow('3','Củng cố kiến thức và kết thúc bài','',36,$r,'cungco');
                $html.=$this->pLessonRow('4','Hướng dẫn tự học','',24,$r,'tuhoc',true);
                $html.=$this->pGrid([36,64],[],[['Nguồn tài liệu tham khảo',$this->pc($this->pLines(3,$this->v($r,'tailieuthamkhao'),5),1,1,true)]],0,22,9).$this->pSign('TRƯỞNG KHOA/TRƯỞNG TỔ MÔN','GIÁO VIÊN','');
            }else{
                $html.=$this->pLessonRow('2',$number===6?'Hướng dẫn ban đầu':'Giới thiệu chủ đề',$number===6?'(Hướng dẫn thực hiện công nghệ; phân công vị trí luyện tập)':'(Giới thiệu nội dung chủ đề cần giải quyết: yêu cầu kỹ thuật, tiêu chuẩn kiến thức kỹ năng)',76,$r,'huongdanbandau');
                $html.=$this->pLessonRow('3',$number===6?'Hướng dẫn thường xuyên':'Giải quyết vấn đề',$number===6?'(Hướng dẫn học sinh rèn luyện để hình thành và phát triển kỹ năng)':'(Hướng dẫn học sinh rèn luyện để hình thành, phát triển năng lực trong sự phối hợp của thầy)',123,$r,'huongdanthuongxuyen');
                $html.=$this->pLessonRow('4',$number===6?'Hướng dẫn kết thúc':'Kết thúc vấn đề',$number===6?'(Nhận xét kết quả rèn luyện, lưu ý các sai sót và cách khắc phục)':'(Củng cố kiến thức, củng cố kỹ năng rèn luyện)',35,$r,'ketthuc');
                $continuation = $number===6 ? $this->pLines(3,'',5) : '<i>(Nhận xét kết quả rèn luyện, lưu ý các sai sót và cách khắc phục, kế hoạch hoạt động tiếp theo)</i>'.$this->pLines(3,'',5);
                $html.='<pagebreak orientation="P" />'.$this->pGrid([6,31,26,26,11],[],[['',$this->pc($continuation,1,1,true),'','','']],0,$number===6?18:38,9,0,0).$this->pLessonRow('5',$number===6?'Hướng dẫn tự rèn luyện':'Hướng dẫn tự học','',26,$r,'tuhoc',true).'<h3>'.($number===6?'IV.':'VI.').' RÚT KINH NGHIỆM TỔ CHỨC THỰC HIỆN:</h3>'.$this->pLines($number===6?13:6,$this->v($r,'rutkinhnghiem'),6).$this->pSign('TRƯỞNG KHOA/TRƯỞNG TỔ MÔN','GIÁO VIÊN','');
            }
        }return$html;
    }

    private function pLessonRow(string $n,string $title,string $hint,float $height,array $record,string $key,bool $merge=false):string
    {
        $content='<b><u>'.$this->e($title).'</u></b><br><i>'.$this->e($hint).'</i><br>'.nl2br($this->e($this->v($record,$key.'noidung')));
        if(!$merge)$content.=$this->pLines(max(0,(int)floor(($height-($hint===''?12:30))/6)),'',6);
        $row=[$n,$this->pc($content,1,1,true)];
        if($merge)$row[]=$this->pc($this->pLines(4,$this->v($record,$key.'giaovien'),5),2,1,true);else$row=[...$row,$this->v($record,$key.'giaovien'),$this->v($record,$key.'hocsinh')];
        $row[]=$this->v($record,$key.'thoigian');
        return'<div class="lesson-table">'.$this->pGrid([6,31,26,26,11],[],[$row],0,$height,9,0,0).'</div>';
    }

    private function pUsage(int $number):string
    {
        if($number===3)$text=[
            'Sổ lên lớp dùng để theo dõi toàn bộ quá trình học tập và kết quả học tập của học sinh và giảng dạy của giáo viên trong toàn khóa học. Sổ được đặt tại Phòng đào tạo hoặc bộ phận phụ trách đào tạo (gọi chung là phòng đào tạo). Phòng đào tạo quy định hình thức nhận sổ trước khi lên lớp và trả sổ sau khi kết thúc ngày học đối với người giáo viên sử dụng.',
            'Phương pháp ghi sổ:',
            '1. Danh sách giáo viên giảng dạy và thời khóa biểu do Phòng đào tạo ghi trong một năm học (đối với trung cấp nghề, cao đẳng nghề), một khóa học (đối với sơ cấp nghề).',
            '2. Theo dõi ngày học tập của học sinh: học sinh có mặt để trống; vắng mặt có lý do ghi P; vắng mặt không lý do ghi K. Trong thời gian học sinh nghỉ một số giờ học trong ngày thì giáo viên ghi số giờ nghỉ của học sinh, sinh viên.',
            '3. Ghi điểm theo quy định ban hành kèm theo Quyết định số 14/2007/QĐ-BLĐTBXH ngày 24/5/2007 của Bộ trưởng Bộ Lao động - Thương binh và Xã hội ban hành Quy chế thi, kiểm tra và công nhận tốt nghiệp trong dạy nghề hệ chính quy. Điểm ghi trong sổ là điểm kiểm tra định kỳ, điểm kiểm tra kết thúc môn học/mô-đun, điểm tổng kết môn học/mô-đun.',
            '4. Quyết định học lại môn học/mô-đun là tên các môn học/mô-đun học sinh, sinh viên phải học theo hình thức học lại bổ sung môn học/mô-đun (đối với môn học/mô-đun không phải là tiền đề để học các môn học/mô-đun tiếp theo), học lại với khóa sau (đối với các môn học/mô-đun là điều kiện tiền đề của các môn học/mô-đun tiếp theo).',
            '5. Phần tổng hợp đánh giá cuối năm - cuối khóa sử dụng cho quản lý lớp học theo năm học và cả khóa học tùy theo thời gian của khóa học. Riêng đối với đào tạo nghề trình độ sơ cấp chỉ sử dụng cho khóa học.',
            '6. Hướng dẫn ghi điểm lần 1 (lần 2).',
        ];else$text=[
            'Sổ tay giáo viên là sổ ghi chép của giáo viên trong quá trình quản lý giảng dạy trên lớp học. Nội dung phản ánh kế hoạch học tập và các quá trình diễn ra trong triển khai kế hoạch học tập môn học/mô-đun mà giáo viên tham gia giảng dạy.',
            'Phương pháp ghi:',
            '1. Thông tin lớp học và thời khóa biểu được xác định khi bắt đầu tổ chức giảng dạy môn học/mô-đun.',
            '- Phần trình độ đầu vào và hình thức đánh giá đầu vào ghi yêu cầu trình độ đầu vào quy định chung của học sinh tham gia học tập môn học/mô-đun hoặc yêu cầu các môn học/mô-đun học sinh phải tham gia trước khi học tập môn học/mô-đun; hình thức đánh giá đầu vào là hình thức tuyển sinh hoặc đánh giá kết quả các môn học/mô-đun học sinh đã học làm tiền đề cho việc học tập môn học/mô-đun.',
            '- Phần phương thức tổ chức đào tạo ghi dự kiến những nét cơ bản của phương thức tổ chức lớp học, phương pháp giảng dạy và đánh giá kết quả học tập trong giảng dạy môn học/mô-đun.',
            '2. Kết quả học tập ghi kết quả kiểm tra định kỳ, kiểm tra kết thúc môn học/mô-đun theo quy định ban hành kèm theo Quyết định số 14/2007/QĐ-BLĐTBXH ngày 24/5/2007 của Bộ trưởng Bộ Lao động - Thương binh và Xã hội ban hành Quy chế thi, kiểm tra và công nhận tốt nghiệp trong dạy nghề hệ chính quy.',
            '3. Đánh giá quá trình giảng dạy môn học/mô-đun ghi đặc điểm chung các hoạt động của lớp học, các phương thức tổ chức đào tạo đã đưa ra, đánh giá tác động của các phương thức tổ chức đào tạo, nội dung đào tạo đến kết quả học tập chung của lớp học.',
            '4. Theo dõi giờ lên lớp của học sinh/sinh viên.',
            '5. Ghi học sinh/sinh viên cá biệt.',
        ];return'<div class="usage">'.implode('',array_map(fn($t)=>'<p>'.$this->e($t).'</p>',$text)).'</div>';
    }
}
