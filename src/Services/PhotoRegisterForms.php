<?php
declare(strict_types=1);

namespace AIVANBAN\Services;

trait PhotoRegisterForms
{
    private function issueRegister(array $course,int $number,string $title,string $learnerLabel,array $students,array $graduationByStudent,bool $diploma):string
    {
        $heads=['Số TT','HỌ TÊN '.mb_strtoupper($learnerLabel,'UTF-8'),'NGÀY THÁNG NĂM SINH','NƠI SINH','NGHỀ ĐÀO TẠO','KHÓA: TỪ ... ĐẾN ...'];
        if($diploma)$heads[]='HÌNH THỨC ĐÀO TẠO';
        $heads=[...$heads,'SỐ QUYẾT ĐỊNH CÔNG NHẬN TỐT NGHIỆP','SỐ HIỆU '.($diploma?'BẰNG':'CHỨNG CHỈ'),'NGÀY CẤP '.($diploma?'BẰNG':'CHỨNG CHỈ'),'NGÀY NHẬN '.($diploma?'BẰNG':'CHỨNG CHỈ'),'CHỮ KÝ NGƯỜI NHẬN','GHI CHÚ'];
        $rows=[];foreach($students as$i=>$s){$g=$graduationByStudent[$s['student_code']]??[];$r=[$i+1,$s['full_name'],$s['date_of_birth'],$s['place_of_birth'],$s['major'],$s['course']];if($diploma)$r[]=$this->v($g,'hinhthucdaotao')?:$this->v($course,'hinhthucdaotao');$rows[]=[...$r,$this->v($g,'soquyetdinh'),$this->v($g,'sohieubangchungchi'),$this->date($this->v($g,'ngaycap')),$this->date($this->v($g,'ngaynhan')),'',$this->v($g,'ghichu')];}
        $widths=$diploma?[4,14,8,8,10,9,7,10,9,7,7,4,3]:[4,14,9,9,10,10,12,10,8,7,4,3];
        $html=$this->pCover($number,$title,$course).$this->pCertificate($number,$title,$course);
        foreach(array_chunk($rows?:[array_fill(0,count($heads),'')],22)as$chunk)$html.=$this->pPage($number,'','L').$this->pOfficialHeader($course,true).'<h2>'.$this->e($title).'</h2>'.$this->pGrid($widths,[$heads],$chunk,22,6,7.2);
        return$html;
    }

    private function copyRegister(array $course,int $number,string $title,string $learnerLabel,array $students,array $graduationByStudent):string
    {
        if($number===10&&!str_contains($title,'TỪ SỔ GỐC'))$title.=' TỪ SỔ GỐC';
        $heads=['Số TT','HỌ TÊN '.mb_strtoupper($learnerLabel,'UTF-8'),'NGÀY THÁNG NĂM SINH','NƠI SINH','SỐ QUYẾT ĐỊNH CÔNG NHẬN TỐT NGHIỆP','SỐ HIỆU '.($number===10?'CHỨNG CHỈ':'BẰNG')];
        $heads=[...$heads,...($number===10?['SỐ LƯỢNG BẢN SAO','HÌNH THỨC CẤP BẢN SAO','NGÀY CẤP BẢN SAO','SAO LỤC TỪ SỔ BẢN CHÍNH']:['SAO LỤC TỪ SỔ BẢN CHÍNH','HÌNH THỨC CẤP BẢN SAO','NGÀY CẤP BẢN SAO','SỐ LƯỢNG BẢN SAO']),'CHỮ KÝ NGƯỜI NHẬN'];
        $rows=[];foreach($students as$i=>$s){$g=$graduationByStudent[$s['student_code']]??[];$source='Số sổ: '.($this->v($g,'soso')?:'.......')."\nTrang: ".($this->v($g,'sotrang')?:'.......');$copies=$this->v($g,'soluongbansao')?:$this->v($g,'soluong');$r=[$i+1,$s['full_name'],$s['date_of_birth'],$s['place_of_birth'],$this->v($g,'soquyetdinh'),$this->v($g,'sohieubangchungchi')];$rows[]=[...$r,...($number===10?[$copies,$this->v($g,'hinhthuccapbansao'),$this->date($this->v($g,'ngaycapbansao')),$source]:[$source,$this->v($g,'hinhthuccapbansao'),$this->date($this->v($g,'ngaycapbansao')),$copies]),''];}
        $widths=$number===10?[4,15,9,9,12,11,7,8,8,11,6]:[4,15,9,9,12,11,11,8,8,7,6];
        $html=$this->pCover($number,$title,$course).$this->pCertificate($number,$title,$course);
        foreach(array_chunk($rows?:[array_fill(0,11,'')],12)as$chunk){while(count($chunk)<12){$r=array_fill(0,11,'');$r[$number===10?9:6]="Số sổ: .......\nTrang: .......";$chunk[]=$r;}$html.=$this->pPage($number,'','L').$this->pOfficialHeader($course,true).'<h2>'.$this->e($title).'</h2>'.$this->pGrid($widths,[$heads],$chunk,0,10,7.6);}
        return$html;
    }

    private function profileBook(int $number,string $title,string $learnerLabel,array $course,array $students,array $gradesByStudent,array $attendanceByStudent,array $graduationByStudent,array $moduleNames):string
    {
        $html=$this->pCover($number,$title,$course).$this->pCertificate($number,$title,$course);
        foreach($students as$s){
            $grades=$gradesByStudent[$s['student_code']]??[];$g=$graduationByStudent[$s['student_code']]??[];
            $html.=$this->pPage($number).$this->pOfficialHeader($course,true).'<h2>I. SƠ LƯỢC LÝ LỊCH</h2>'.$this->pBiography($s,$number===11);
            if($number===11){
                $html.='<h2>II. KẾT QUẢ HỌC TẬP TOÀN KHÓA</h2>'.$this->pScResult($s,$g,$grades,$moduleNames,$course);
                continue;
            }
            $byYear=[1=>[],2=>[],3=>[],4=>[]];$unknown=false;
            foreach($grades as$r){$year=$this->v($r,'namthu');if(!preg_match('/^[1-4]$/',$year)){$year='1';$unknown=true;}$byYear[(int)$year][]=$r;}
            $html.='<h2>II. KẾT QUẢ HỌC TẬP TỪNG NĂM</h2>'.$this->pYearGrid(array_slice($byYear[1],0,6),array_slice($byYear[2],0,6),$moduleNames,$s['course'],$unknown?'':'1','2');
            $html.=$this->pPage($number).$this->pYearGrid(array_slice($byYear[3],0,6),array_slice($byYear[4],0,6),$moduleNames,$s['course'],'3','4').'<h2>III. KẾT QUẢ TỐT NGHIỆP</h2>'.$this->pGraduation($g,$grades);
            // Continue overflow on the same paired year layout, without dropping or duplicating records.
            foreach([[1,2],[3,4]]as[$a,$b])for($offset=6;$offset<max(count($byYear[$a]),count($byYear[$b]));$offset+=6){$html.=$this->pPage($number).$this->pYearGrid(array_slice($byYear[$a],$offset,6),array_slice($byYear[$b],$offset,6),$moduleNames,$s['course'],(string)$a,(string)$b);}
        }return$html;
    }

    private function pBiography(array $s,bool $sc):string
    {
        $r=$s['_source']??[];
        $value=fn(string$key):string=>$this->pValue($this->v($r,$key));
        $bio='<p>Họ và tên khai sinh: '.$this->pValue($s['full_name']).' &nbsp; Nam, nữ: '.$this->pValue($s['gender']).'</p><p>Tên thường gọi: '.$value('tenthuonggoi').'</p><p>Sinh ngày: '.$this->pValue($s['date_of_birth']).'</p><p>Nơi sinh: '.$this->pValue($s['place_of_birth']).'</p><p>Quê quán: '.$this->pValue($s['hometown']).'</p><p>Nơi đăng ký thường trú: '.$this->pValue($s['permanent_address']).'</p><p>Dân tộc: '.$this->pValue($s['ethnicity']).' &nbsp; Tôn giáo: '.$this->pValue($s['religion']).'</p><p>Trình độ học vấn trước khi vào học: '.$value('trinhdohocvan').'</p><p>Ngày tham gia Đảng CSVN: '.$value('ngayvaodang').' &nbsp; Ngày chính thức: '.$value('ngaychinhthuc').'</p><p>Ngày kết nạp vào Đoàn TNCS Hồ Chí Minh: '.$value('ngayvaodoan').'</p>';
        foreach(['bo'=>'bố','me'=>'mẹ','vochong'=>'vợ (chồng)']as$key=>$label)$bio.='<p>Họ và tên '.$label.': '.$value('hoten'.$key).' &nbsp; Nghề nghiệp: '.$value('nghenghiep'.$key).'</p>';
        $bio.='<p>Đối tượng thuộc diện chính sách: '.$value('dienchinhsach').'</p><p>Nghề nghiệp làm trước khi vào học: '.$value('nghenghieptruockhihoc').'</p><p>Địa chỉ liên lạc: '.$value('diachilienlac').' &nbsp; Điện thoại: '.$this->pValue($s['phone']).'</p><p>'.($sc?'Nơi làm việc sau khi kết thúc khóa học (nếu có): '.$value('noilamviecsaukhoahoc'):'Nguyện vọng việc làm sau khi kết thúc khóa học: '.$value('nguyenvongvieclam')).'</p>'.$this->pLines(2,'',4);
        return'<table class="bio-table"><tr><td class="bio-register" style="vertical-align:top;width:34mm"><table class="registration-frame"><tr><td class="registration-number">Số đăng ký<br>'.$value('sodangky').'</td></tr><tr><td class="registration-photo">Ảnh 3 x 4</td></tr></table></td><td class="bio-content" style="vertical-align:top;font-size:9pt;line-height:1.65">'.$bio.'</td></tr></table>';
    }

    private function pYearGrid(array $left,array $right,array $moduleNames,string $course,string $yearLeft,string $yearRight):string
    {
        // The photographed right half has three periodic cells; the left has four.
        $head=[[$this->pc('NĂM THỨ: '.($yearLeft?:'.......').'  NIÊN KHÓA: '.$course,8),$this->pc('NĂM THỨ: '.($yearRight?:'.......').'  NIÊN KHÓA: '.$course,7)],[$this->pc('Môn học/Mô-đun',1,3),$this->pc('Kết quả học tập Môn học/Mô-đun',7),$this->pc('Môn học/Mô-đun',1,3),$this->pc('Kết quả học tập Môn học/Mô-đun',6)],[$this->pc('Kiểm tra định kỳ',4,2),$this->pc('Kiểm tra hết MH/MĐ',2),$this->pc('Tổng kết',1,2),$this->pc('Kiểm tra định kỳ',3,2),$this->pc('Kiểm tra hết MH/MĐ',2),$this->pc('Tổng kết',1,2)],['Lần 1','Lần 2','Lần 1','Lần 2']];
        $body=[];
        for($i=0;$i<6;$i++){
            $row=[];foreach([[$left[$i]??[],4],[$right[$i]??[],3]]as[$g,$n]){$code=$this->v($g,'mamonmodun');$row[]=$moduleNames[$code]??($this->v($g,'tenmonmodun')?:$code);foreach(range(1,$n)as$d){$v=$this->v($g,'diemdinhky'.$d);if($d===$n){$extras=[];foreach(range($n+1,6)as$extra){$x=$this->v($g,'diemdinhky'.$extra);if($x!=='')$extras[]=$x;}if($extras!==[])$v=implode('; ',array_filter([$v,...$extras],static fn($x)=>$x!==''));}$row[]=$v;}$row=[...$row,$this->v($g,'diemketthuclan1'),$this->v($g,'diemketthuclan2'),$this->v($g,'diemtongket')];}$body[]=$row;
        }
        foreach(['Xếp loại học tập:','Xếp loại rèn luyện:','Khen thưởng, kỷ luật:']as$label)$body[]=[$this->pc($label.' ................................',8),$this->pc($label.' ................................',7)];
        return$this->pGrid([15,3.5,3.5,3.5,3.5,7,7,7,15,4.6667,4.6667,4.6666,7,7,7],$head,$body,0,6,8,7);
    }

    private function pGraduation(array $g,array $grades):string
    {
        $exams=array_values(array_filter($grades,fn($r)=>$this->v($r,'noidungthi')!==''));
        $note='<b>Nhận xét tóm tắt quá trình học tập, rèn luyện:</b>'.$this->pLines(6,$this->v($g,'nhanxet'),5);
        $decision='Quyết định công nhận tốt nghiệp: '.$this->pValue($this->v($g,'soquyetdinh')).'<br>Cấp ngày: '.$this->pValue($this->date($this->v($g,'ngayquyetdinh')));
        $diploma='Bằng tốt nghiệp số: '.$this->pValue($this->v($g,'sohieubangchungchi')).'<br>Xếp loại tốt nghiệp: '.$this->pValue($this->v($g,'xeploaitotnghiep')).'<br>Ngày cấp: '.$this->pValue($this->date($this->v($g,'ngaycap')));
        $rows=[[$this->pc('Số TT',1,3),$this->pc('<b>THI TỐT NGHIỆP</b>',3,1,true),$this->pc($note,1,8,true)],[$this->pc('Nội dung thi',1,2),$this->pc('Kết quả thi',2)],['Lần 1','Lần 2']];
        for($i=0;$i<11;$i++){$e=$exams[$i]??[];$r=[$e===[]?'':$i+1,$this->v($e,'noidungthi'),$this->v($e,'diemthilan1'),$this->v($e,'diemthilan2')];if($i===5)$r[]=$this->pc($decision,1,3,true);if($i===8)$r[]=$this->pc($diploma,1,3,true);$rows[]=$r;}
        $rows[]=[$this->pc('Điểm trung bình chung: '.$this->v($g,'diemtrungbinhchung'),4),$this->pc('<div class="center">Người tổng hợp<br><i>(Ký, ghi rõ họ tên)</i></div>',1,3,true)];
        $rows[]=[$this->pc('Điểm xếp loại tốt nghiệp: '.$this->v($g,'diemxeploaitotnghiep'),4)];
        $rows[]=[$this->pc('Điểm xếp loại rèn luyện: '.$this->v($g,'diemxeploairenluyen'),4)];
        return$this->pGrid([5,30,6,6,53],[],$rows,0,7,8.5);
    }

    private function pScResult(array $s,array $g,array $grades,array $moduleNames,array $course):string
    {
        $meta='Lớp: '.$this->pValue($s['class_code']).' Khóa: '.$this->pValue($s['course']).'<br>Thời gian đào tạo: '.$this->pValue($this->v($course,'thoigiankhoa')).'<br>(Từ '.$this->pValue($this->date($this->v($course,'ngaybatdau'))).' đến '.$this->pValue($this->date($this->v($course,'ngayketthuc'))).')';
        $head=[[$this->pc($meta,5,1,true),$this->pc('Kết quả học tập cuối khóa',3)],[$this->pc('Số TT',1,2),$this->pc('Tên mô-đun/môn học',1,2),$this->pc('Kiểm tra hết MĐ/MH',2),$this->pc('Điểm tổng kết MĐ/MH',1,2),$this->pc('Điểm kiểm tra kết thúc khóa học',2),$this->pc('Xếp loại kết quả rèn luyện: '.$this->v($g,'xeploairenluyen'),1,2)],['Lần 1','Lần 2','Lần 1','Lần 2']];
        $rows=[];
        for($i=0;$i<max(7,count($grades));$i++){$r=$grades[$i]??[];$code=$this->v($r,'mamonmodun');$row=[$r===[]?'':$i+1,$moduleNames[$code]??($this->v($r,'tenmonmodun')?:$code),$this->v($r,'diemketthuclan1'),$this->v($r,'diemketthuclan2'),$this->v($r,'diemtongket')];
            if($i===0)$row=[...$row,$this->v($g,'diemthilan1'),$this->v($g,'diemthilan2'),$this->pc('Tóm tắt nhận xét: '.$this->v($g,'nhanxet'),1,3)];
            elseif($i===1)$row[]=$this->pc('Điểm xếp loại tốt nghiệp: '.$this->v($g,'diemxeploaitotnghiep'),2,2);
            elseif($i===2){}
            elseif($i===3)$row[]=$this->pc('Quyết định công nhận tốt nghiệp số: '.$this->v($g,'soquyetdinh').' ngày '.$this->date($this->v($g,'ngayquyetdinh')),3,2);
            elseif($i===4){}
            elseif($i===5)$row[]=$this->pc('Chứng chỉ sơ cấp nghề số: '.$this->v($g,'sohieubangchungchi'),3);
            elseif($i===6)$row[]=$this->pc('Ngày cấp chứng chỉ sơ cấp nghề: '.$this->date($this->v($g,'ngaycap')),3);
            else$row[]=$this->pc('',3);
            $rows[]=$row;
        }
        return$this->pGrid([6,28,7,7,12,10,10,20],$head,$rows,0,7,8);
    }
}
