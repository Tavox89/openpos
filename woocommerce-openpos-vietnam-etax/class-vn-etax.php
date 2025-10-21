<?php
if(!class_exists('OP_Vn_Etax')) {
   

    class OP_Vn_Etax {
        private $doc;
        private $hDon;
        private $dlHDon;
        private $dsHHDVu;
        private $nDHDon;
        private $Pban = '2.0';
    
        public function __construct() {
            $this->doc = new DOMDocument('1.0', 'UTF-8');
            $this->doc->formatOutput = true;
    
            $this->hDon = $this->doc->createElementNS('http://kekhaithue.gdt.gov.vn/HDon', 'HDon');
            $this->doc->appendChild($this->hDon);
    
            $this->dlHDon = $this->doc->createElement('DLHDon');
            $this->hDon->appendChild($this->dlHDon);
    
            $this->dsHHDVu = $this->doc->createElement('DSHHDVu');
            $this->nDHDon = $this->doc->createElement('NDHDon');
        }
    
        public function setThongTinChung($data) {
            $ttChung = $this->doc->createElement('TTChung');
            $ttCKhac = $this->doc->createElement('TTCKhac');

            $fields = [
               // 'Pban' => '2.0',
                'THDon', 'KHMSHDon', 'KHHDon', 'SHDon',
                'NLap', 'DVTTe', 'TGia','HTTToan'
            ];
            $ttChung->appendChild($this->doc->createElement('Pban', $this->Pban));
            foreach ($fields as $key) {
                
                $value = isset($data[$key]) ? $data[$key] : '';
                $ttChung->appendChild($this->doc->createElement($key, $value));
            }
            if(isset($data['TTCKhac'])) {
                foreach ($data['TTCKhac'] as $key => $val) {
                    
                     $tTin = $this->doc->createElement('TTin');
                     $tFields = array(
                        'TTruong',
                        'KDLieu',
                        'DLieu',
                     );
                    foreach ($tFields as $tKey) {
                        $tValue = isset($val[$tKey]) ? $val[$tKey] : '';
                        $tTin->appendChild($this->doc->createElement($tKey, $tValue));
                    }
                    $ttCKhac->appendChild($tTin);
                }
                $ttChung->appendChild($ttCKhac);
            }

    
            $this->dlHDon->appendChild($ttChung);
        }

        
    
        public function setNguoiBan($data) {
            $nBan = $this->doc->createElement('NBan');
            foreach ($data as $key => $val) {
                $escaped = $val;
                $nBan->appendChild($this->doc->createElement($key, $escaped));
            }
            $this->nDHDon->appendChild($nBan);
        }
    
        public function setNguoiMua($data) {
            $nMua = $this->doc->createElement('NMua');
            foreach ($data as $key => $val) {
                $escaped = $val;
                $nMua->appendChild($this->doc->createElement($key, $escaped));
            }
            $this->nDHDon->appendChild($nMua);
        }
    
        public function addHangHoa($item) {
            $hhdVu = $this->doc->createElement('HHDVu');
            foreach ($item as $key => $val) {
                $escaped = $val;
                $hhdVu->appendChild($this->doc->createElement($key, $escaped));
            }
            $this->dsHHDVu->appendChild($hhdVu);
        }
    
        public function setThanhToan($data) {
            $tToan = $this->doc->createElement('TToan');
            foreach ($data as $key => $val) {
                
                $tToan->appendChild($this->doc->createElement($key, $val));
            }
            $this->nDHDon->appendChild($tToan);
        }
    
        public function generate($filePath = 'hoadon.xml') {
            $this->nDHDon->appendChild($this->dsHHDVu);
            $this->dlHDon->appendChild($this->nDHDon);
            
            $this->doc->save($filePath);
            return $filePath;
        }
    }
    
}