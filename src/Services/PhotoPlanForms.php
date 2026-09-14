<?php
declare(strict_types=1);

namespace AIVANBAN\Services;

trait PhotoPlanForms
{
    private function trainingPlan(array $course,int $number,string $level,array $modules,array $schedule):string
    {
        $sc=$number===8;
        $html=$this->pLabel($number).$this->pOfficialHeader($course).'<h1>KẾ HOẠCH ĐÀO TẠO</h1>';
        $html.='<div class="written"><p>1. Nghề đào tạo: '.$this->pValue($this->v($course,'nghedaotao')).' &nbsp; Mã nghề: '.$this->pValue($this->v($course,'manghe')).($sc?' &nbsp; Lớp: '.$this->pValue($this->v($course,'malop')):'').'</p><p>2. Trình độ đào tạo: '.$this->e($level).' nghề</p><p>3. Đối tượng tuyển sinh: '.$this->pValue($this->v($course,'doituong')).'</p><p><i>(Trình độ học vấn làm căn cứ xét tuyển)</i></p><p>4. Mục tiêu đào tạo:</p>'.$this->pLines(14,$this->v($course,'muctieu'),6);
        if($sc){
            $html.='<p>5. Thời gian khóa học: '.$this->pValue($this->v($course,'thoigiankhoa')).'</p><p>(Từ ngày '.$this->pValue($this->date($this->v($course,'ngaybatdau'))).' đến ngày '.$this->pValue($this->date($this->v($course,'ngayketthuc'))).')</p><p>6. Thời gian học tập: '.$this->pValue($this->v($course,'sotuanhoc')).' tuần, trong đó thời gian ôn và kiểm tra: '.$this->pValue($this->v($course,'sotuanonkiemtra')).' tuần.</p><p>7. Thời gian khai, bế giảng: '.$this->pValue($this->v($course,'sotuankhaibegiang')).' tuần.</p><p>8. Quyết định phê duyệt chương trình: '.$this->pValue($this->v($course,'quyetdinhchuongtrinh')).'</p></div>';
            $head=[[$this->pc('Số TT',1,2),$this->pc('MÔN HỌC/MÔ-ĐUN',1,2),$this->pc('THỜI GIAN ĐÀO TẠO (giờ)',3),$this->pc('LỊCH KIỂM TRA HẾT MH/MĐ',1,2)],['Lý thuyết','Thực hành','Ôn, Kiểm tra']];
            $rows=[];foreach($modules as$i=>$m)$rows[]=[$i+1,$this->v($m,'tenmonmodun'),$this->v($m,'lythuyet'),$this->v($m,'thuchanh'),$this->v($m,'onkiemtra')?:$this->v($m,'kiemtra'),$this->date($this->v($m,'lichkiemtra'))?:'.../..../....'];
            while(count($rows)<9)$rows[]=['','','','','','.../..../....'];
            $rows[]=['',$this->pc('KIỂM TRA KẾT THÚC KHÓA HỌC',4),"Từ ngày .../.../....\nđến .../.../...."];
            $html.=$this->pPage(8).'<h3>9. Phân bổ thời gian đào tạo.</h3>'.$this->pGrid([7,23,14,14,14,28],$head,$rows,0,9,9);
            $lined=$this->pc($this->pLines(8,'',5),1,1,true);
            $html.='<h3>10. Quy định kiểm tra kết thúc khóa học.</h3>'.$this->pGrid([34,22,22,22],[['BÀI TẬP KỸ NĂNG TỔNG HỢP','ĐIỀU KIỆN KIỂM TRA','PHƯƠNG PHÁP ĐÁNH GIÁ','GHI CHÚ']],[[$lined,$lined,$lined,$lined]],0,45,9).$this->pSign('','HIỆU TRƯỞNG/GIÁM ĐỐC');
            return$html.$this->pPage(8,'HƯỚNG DẪN SỬ DỤNG').$this->pPlanUsage(true);
        }
        $html.='<p>5. Khóa học: '.$this->pValue($this->v($course,'tenkhoa')).'</p><p>6. Thời gian khóa học: '.$this->pValue($this->v($course,'thoigiankhoa')).'</p><p>(Từ ngày '.$this->pValue($this->date($this->v($course,'ngaybatdau'))).' đến ngày '.$this->pValue($this->date($this->v($course,'ngayketthuc'))).')</p><p>7. Thời gian học tập: '.$this->pValue($this->v($course,'sotuanhoc')).' tuần, trong đó ôn và thi: '.$this->pValue($this->v($course,'gioonthi')).' giờ.</p><p>8. Thời gian khai, bế giảng, nghỉ lễ, hè và dự phòng: '.$this->pValue($this->v($course,'sotuanduphong')).' tuần.</p><p>9. Quyết định phê duyệt chương trình: '.$this->pValue($this->v($course,'quyetdinhchuongtrinh')).'</p></div>';
        $html.=$this->pPage(12,'HƯỚNG DẪN SỬ DỤNG').$this->pPlanUsage(false);
        $html.=$this->pPage(12,'I. LỊCH HỌC TOÀN KHÓA','L').$this->pCourseCalendar($schedule).$this->pLegend().'<p><i>Ghi chú: Các cơ sở quy định các ký hiệu cụ thể cho từng nội dung sao cho không trùng lặp.</i></p><h3>II. PHÂN BỔ THỜI GIAN HỌC TẬP</h3>';
        $head=[[$this->pc('TT',1,3),$this->pc('Các môn học',1,3),$this->pc('Tổng số (giờ)',1,3),$this->pc('Lý thuyết (giờ)',1,3),$this->pc('Thực hành (giờ)',1,3),$this->pc('Thi (giờ - học kỳ)',1,3),$this->pc('Kế hoạch giảng dạy',6)],[$this->pc('Năm 1',2),$this->pc('Năm 2',2),$this->pc('Năm 3',2)],['Học kỳ 1','Học kỳ 2','Học kỳ 3','Học kỳ 4','Học kỳ 5','Học kỳ 6']];
        $rows=[];
        foreach($modules as$i=>$m){$r=[$i+1,$this->v($m,'tenmonmodun'),$this->v($m,'tonggio'),$this->v($m,'lythuyet'),$this->v($m,'thuchanh'),$this->v($m,'kiemtra')];foreach(range(1,6)as$term)$r[]=$this->v($m,'hocky'.$term);$rows[]=$r;}
        while(count($rows)<2)$rows[]=array_fill(0,12,'');
        $rows[]=['','Tổng số',...array_fill(0,10,'')];
        $html.=$this->pGrid([5,25,8,8,8,8,...array_fill(0,6,38/6)],$head,$rows,0,6,8);
        $head=[[$this->pc('STT',1,2),$this->pc('Nội dung thi tốt nghiệp',1,2),$this->pc('Dự kiến thời gian thi tốt nghiệp',3),$this->pc('Hình thức',1,2),$this->pc('Phương pháp đánh giá',1,2),$this->pc('Ghi chú',1,2)],['Thời gian ôn thi (ngày)','Thời gian thi (giờ)','Kế hoạch thi từ ngày ... đến ngày ...']];
        $html.=$this->pPage(12,'','L').$this->pGrid([5,26,12,12,15,10,12,8],$head,[['1','','','','','','',''],['2','','','','','','',''],['3','','','','','','',''],array_fill(0,8,'')],0,14,10).$this->pSign('','HIỆU TRƯỞNG');
        return$html;
    }

    private function pCourseCalendar(array $schedule):string
    {
        $visible=array_fill_keys([...range(1,5),...range(46,52)],true);
        foreach($schedule as$r){$w=(int)$this->v($r,'tuan');if($w>=1&&$w<=52)$visible[$w]=true;}ksort($visible);$weeks=[];$prev=0;
        foreach(array_keys($visible)as$w){if($w>$prev+1)$weeks[]=null;$weeks[]=$w;$prev=$w;}
        $n=count($weeks);$heads=[['Tháng',...array_fill(0,$n,'')],['Tuần',...array_map(static fn($w)=>$w===null?'............':(string)$w,$weeks)]];
        $rows=[['Năm học',...array_fill(0,$n,'')]];
        foreach([1=>'I',2=>'II',3=>'III',4=>'IV']as$year=>$label){$row=[$label];foreach($weeks as$w){$values=[];foreach($schedule as$r){if($w!==null&&(int)$this->v($r,'tuan')===$w&&(int)$this->v($r,'namthu')===$year){$value=$this->v($r,'kyhieu')?:$this->v($r,'noidung');if($value!=='')$values[]=$value;}}$row[]=implode('; ',$values);}$rows[]=$row;}
        return$this->pGrid([15,...array_fill(0,$n,85/$n)],$heads,$rows,0,5,8);
    }

    private function pLegend():string
    {
        $labels=['Khai bế giảng','Văn hóa THPT','Môn chung','Môn học/mô-đun đào tạo nghề','Thi tốt nghiệp (kiểm tra kết thúc khóa học)','Nghỉ hè, lễ','Lao động/ngoại khóa','Thực tập tại doanh nghiệp'];
        $html='<table class="photo-legend">';foreach(array_chunk($labels,4)as$row){$html.='<tr>';foreach($row as$l)$html.='<td><div class="key">&nbsp;</div><div>'.$l.'</div></td>';$html.='</tr>';}return$html.'</table>';
    }

    private function pPlanUsage(bool $sc):string
    {
        $items=$sc?[
            'Bảng kế hoạch đào tạo trình độ sơ cấp nghề được Phòng đào tạo hoặc bộ phận phụ trách đào tạo (gọi chung là phòng đào tạo) xây dựng vào đầu khóa học và được Hiệu trưởng hoặc người đứng đầu cơ sở dạy nghề phê duyệt và thông báo công khai cho giáo viên và học sinh vào thời điểm mở đầu của khóa học.',
            'Phương pháp ghi',
            '1. Mục tiêu đào tạo ghi đúng như mục tiêu đào tạo trong chương trình dạy nghề trình độ sơ cấp đã được phê duyệt.',
            '2. Quyết định tổ chức khóa học: ghi số và tên Quyết định của người đứng đầu cơ sở dạy nghề phê duyệt tổ chức khóa học.',
            '3. Lịch kiểm tra hết môn học/mô-đun ghi thời điểm dự kiến kiểm tra hết môn học/mô-đun.',
            '4. Bài tập kỹ năng tổng hợp ghi dự tóm tắt bài tập kiểm tra kết thúc khóa học hoặc yêu cầu tiêu chuẩn kỹ năng cơ bản, cần thiết phải đạt được của người học sau khi kết thúc khóa học.',
            '5. Điều kiện kiểm tra ghi các điều kiện cần thiết để kiểm tra kết thúc khóa học (địa điểm, máy móc, vật tư, tài liệu...).',
        ]:[
            'Bảng kế hoạch đào tạo trình độ trung cấp nghề, trình độ cao đẳng nghề được Phòng đào tạo hoặc bộ phận phụ trách đào tạo (gọi chung là phòng đào tạo) xây dựng vào đầu khóa học và được Hiệu trưởng hoặc người đứng đầu cơ sở dạy nghề phê duyệt và thông báo công khai cho giáo viên và học sinh vào thời điểm mở đầu của khóa học.',
            'Phương pháp ghi',
            '1. Mục tiêu đào tạo ghi đúng như mục tiêu đào tạo trong chương trình dạy nghề trình độ sơ cấp đã được phê duyệt.',
            '2. Quyết định tổ chức khóa học: ghi số và tên Quyết định của người đứng đầu cơ sở dạy nghề phê duyệt tổ chức khóa học.',
            '3. Lịch học toàn khóa các cơ sở quy định các ký hiệu cụ thể cho từng nội dung sao cho không trùng lặp.',
            '4. Phân bổ thời gian học tập theo từng trình độ trung cấp nghề, trình độ cao đẳng nghề.',
            '5. Lịch thi tốt nghiệp phải cụ thể, thông báo đầy đủ nội dung thi tốt nghiệp, dự kiến thời gian thi, hình thức, phương pháp đánh giá cho giáo viên và học sinh/sinh viên vào thời điểm mở đầu khóa học.',
        ];return'<div class="usage">'.implode('',array_map(fn($s)=>'<p>'.$this->e($s).'</p>',$items)).'</div>';
    }
}
