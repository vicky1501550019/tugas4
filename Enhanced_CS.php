<?php 
require_once('connect.php');
class Stemmer {

  public function cekKamus($kata){
   // cari di database;
   global $conn;
	$sql = "SELECT * from tb_katadasar where katadasar ='$kata' LIMIT 1";
	//echo $sql.'<br/>';
	$result = mysqli_query($conn, $sql) or die(mysqli_error());  
	if(mysqli_num_rows($result)==1){
		return true; // True jika ada
	}else{
		return false; // jika tidak ada FALSE
	}
  }

  /*============= Stemming dengan Metode Nazief and Adriani’s Algorithm ===============================*/
  /*
  DP + DP + DP + root word + DS + PP + P

  DP : Derivation Prefix
  DS : Derivation Suffix
  PP : Possessive Pronoun (Inflection) [ku,mu,nya]
  P : Particle (Inflection) [lah,kah,]
  */

  // Hapus Inflection Suffixes (“-lah”, “-kah”, “-ku”, “-mu”, atau “-nya”)
  public function Del_Inflection_Suffixes($kata){ 
   $kataAsal = $kata;
   if(preg_match('/([km]u|nya|[kl]ah|pun)$/',$kata)){ // Cek Inflection Suffixes
    $__kata = preg_replace('/([km]u|nya|[kl]ah|pun)$/','',$kata);
    if(preg_match('/([klt]ah|pun)$/',$kata)){ // Jika berupa particles (“-lah”, “-kah”, “-tah” atau “-pun”)
     if(preg_match('/([km]u|nya)$/',$__kata)){ // Hapus Possesive Pronouns (“-ku”, “-mu”, atau “-nya”)
      $__kata__ = preg_replace('/([km]u|nya)$/','',$__kata);
      return $__kata__;
     }
    }
    return $__kata; 
   }
   return $kataAsal;
  }
  // Cek Prefix Disallowed Sufixes (Kombinasi Awalan dan Akhiran yang tidak diizinkan)
  public function Cek_Prefix_Disallowed_Sufixes($kata){
   if(preg_match('/^(be)[[:alpha:]]+(i)$/',$kata)){ // be- dan -i
    return true;
   }
   if(preg_match('/^(di)[[:alpha:]]+(an)$/',$kata)){ // di- dan -an    
    return true;
   }
   if(preg_match('/^(ke)[[:alpha:]]+(i|kan)$/',$kata)){ // ke- dan -i,-kan
    return true;
   }
   if(preg_match('/^(me)[[:alpha:]]+(an)$/',$kata)){ // me- dan -an
    return true;
   }
   if(preg_match('/^(se)[[:alpha:]]+(i|kan)$/',$kata)){ // se- dan -i,-kan
    return true;
   }
   return false;
  }

  // Hapus Derivation Suffixes (“-i”, “-an” atau “-kan”)
  public function Del_Derivation_Suffixes($kata){
   $kataAsal = $kata;
   if(preg_match('/(i|an)$/',$kata)){ // Cek Suffixes
    $__kata = preg_replace('/(i|an)$/','',$kata);  
    if($this->cekKamus($__kata)){ // Cek Kamus   
     return $__kata;
    }
    /*-- Jika Tidak ditemukan di kamus --*/
    if(preg_match('/(kan)$/',$kata)){ // cek -kan     
     $__kata__ = preg_replace('/(kan)$/','',$kata);
     if($this->cekKamus($__kata__)){ // Cek Kamus
      return $__kata__;
     }
    }
    if($this->Cek_Prefix_Disallowed_Sufixes($kata)){
     return $kataAsal;
    }
    
   }
   return $kataAsal;
  }
  // Hapus Derivation Prefix (“di-”, “ke-”, “se-”, “te-”, “be-”, “me-”, atau “pe-”)
  public function Del_Derivation_Prefix($kata){
   $kataAsal = $kata; 
    
   /* ------ Tentukan Tipe Awalan ------------*/
   if(preg_match('/^(di|[ks]e)/',$kata)){ // Jika di-,ke-,se-
    $__kata = preg_replace('/^(di|[ks]e)/','',$kata);
    if($this->cekKamus($__kata)){   
     return $__kata; // Jika ada balik
    }
    $__kata__ = $this->Del_Derivation_Suffixes($__kata);
    if($this->cekKamus($__kata__)){
     return $__kata__;
    }
    /*------------end “diper-”, ---------------------------------------------*/
    if(preg_match('/^(diper)/',$kata)){   
     $__kata = preg_replace('/^(diper)/','',$kata);
     if($this->cekKamus($__kata)){   
      return $__kata; // Jika ada balik
     }
     $__kata__ = $this->Del_Derivation_Suffixes($__kata);
     if($this->cekKamus($__kata__)){
      return $__kata__;
     }
     /*-- Cek luluh -r ----------*/
     $__kata = preg_replace('/^(diper)/','r',$kata);
     if($this->cekKamus($__kata)){   
      return $__kata; // Jika ada balik
     }
     $__kata__ = $this->Del_Derivation_Suffixes($__kata);
     if($this->cekKamus($__kata__)){
      return $__kata__;
     }
    }
    /*------------end “diper-”, ---------------------------------------------*/
   }
   if(preg_match('/^([tmbp]e)/',$kata)){ //Jika awalannya adalah “te-”, “me-”, “be-”, atau “pe-”
    
    /*------------ Awalan “te-”, ---------------------------------------------*/
    if(preg_match('/^(te)/',$kata)){ // Jika awalan “te-”,
     /* Cara Menentukan Tipe Awalan Untuk Kata Yang Diawali Dengan “te-”
     Following Characters
     Set 1      Set 2      Set 3   Set 4   Tipe Awalan
    1. “-r-“      “-r-“      -    -    none
    2. “-r-“      Vowel (aiueo)    -    -    ter-luluh
    3. “-r-“      not(vowel or “-r-”)  “-er-“   vowel   ter
    4. “-r-“      not(vowel or “-r-”)  “-er-“   not vowel  ter-
    5. “-r-“      not(vowel or “-r-”)  not “-er-“  -    ter
    6. not(vowel or “-r-”)  “-er-“      vowel   -    none
    7. not(vowel or “-r-”)  “-er-“      not vowel  -    te
     */
     if(preg_match('/^(terr)/',$kata)){ // 1.
      return $kata;
     }
     if(preg_match('/^(ter)[abcdefghijklmnopqrstuvwxyz]/',$kata)){ // 2.
      $__kata = preg_replace('/^(ter)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
     if(preg_match('/^(ter[^aiueor]er[aiueo])/',$kata)){ // 3.
      $__kata = preg_replace('/^(ter)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
     if(preg_match('/^(ter[^aiueor]er[^aiueo])/',$kata)){ // 4.
      $__kata = preg_replace('/^(ter)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
     if(preg_match('/^(ter[^aiueor][^(er)])/',$kata)){ // 5.
      $__kata = preg_replace('/^(ter)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
     if(preg_match('/^(te[^aiueor]er[aiueo])/',$kata)){ // 6.
      return $kata; // return none
     }
     if(preg_match('/^(te[^aiueor]er[^aiueo])/',$kata)){ // 7.
      $__kata = preg_replace('/^(te)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
    }
    /*------------end “te-”, ---------------------------------------------*/
    /*------------ Awalan “me-”, ---------------------------------------------*/
    if(preg_match('/^(me)/',$kata)){ // Jika awalan “me-”,
     /* Cara Menentukan Tipe Awalan Untuk Kata Yang Diawali Dengan “me-”
     Following Characters
     Set 1      Set 2      Set 3   Set 4   Tipe Awalan
    1. “-ng-“      Vowel [kghq]    -    -    meng-
    2. “-ny-“      Vowel (aiueo)    -    -    meny-s
    3. “-m-“      [bfpv]      -    -    mem-
    4. “-n-“      [cdjsz]     -    -    men-
    5. -       -       -    -   me-

     */
     if(preg_match('/^(meng)[aiueokghq]/',$kata)){ // 1.
      $__kata = preg_replace('/^(meng)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }    
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
      /*--- cek luluh k- --------*/
      $__kata = preg_replace('/^(meng)/','k',$kata); // luluh k-
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
     
     if(preg_match('/^(meny)/',$kata)){ // 2.
      $__kata = preg_replace('/^(meny)/','s',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
     if(preg_match('/^(mem)[bfpv]/',$kata)){ // 3.
      $__kata = preg_replace('/^(mem)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
      /*--- cek luluh p- --------*/
      $__kata = preg_replace('/^(mem)/','p',$kata); // luluh p-
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
     if(preg_match('/^(men)[cdjsz]/',$kata)){ // 4.
      $__kata = preg_replace('/^(men)/','',$kata); 
      
      if($this->cekKamus($__kata)){
       
       return $__kata; // Jika ada balik
      }    
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){     
       return $__kata__;
      }    
     }
     if(preg_match('/^(me)/',$kata)){ // 5.
      $__kata = preg_replace('/^(me)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
      /*--- cek luluh t- --------*/
      $__kata = preg_replace('/^(men)/','t',$kata); // luluh t-
      
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
      
     }
    }
    /*------------end “me-”, ---------------------------------------------*/
    /*------------ Awalan “be-”, ---------------------------------------------*/
    if(preg_match('/^(be)/',$kata)){ // Jika awalan “be-”,
     /* Cara Menentukan Tipe Awalan Untuk Kata Yang Diawali Dengan “be-”
     Following Characters
     Set 1      Set 2      Set 3   Set 4   Tipe Awalan
    1. “-r-“      Vowel      -    -    ber-
    2. “-r-“      Not Vowel      -    -    ber-
    3. “-k-“      -       -    -    be-
     */
     if(preg_match('/^(ber)[aiueo]/',$kata)){ // 1.
      $__kata = preg_replace('/^(ber)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata = preg_replace('/^(ber)/','r',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }    
     }
     
     if(preg_match('/(ber)[^aiueo]/',$kata)){ // 2.
      $__kata = preg_replace('/(ber)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
     if(preg_match('/^(be)[k]/',$kata)){ // 3.
      $__kata = preg_replace('/^(be)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
    }
    /*------------end “be-”, ---------------------------------------------*/
    /*------------ Awalan “pe-”, ---------------------------------------------*/
    
    if(preg_match('/^(pe)/',$kata)){ // Jika awalan “pe-”,
     /* Cara Menentukan Tipe Awalan Untuk Kata Yang Diawali Dengan “pe-”
     Following Characters
     Set 1      Set 2      Set 3   Set 4   Tipe Awalan
    1. “-ng-“      Vowel [kghq]    -    -    peng-
    2. “-ny-“      Vowel (aiueo)    -    -    peny-s
    3. “-m-“      [bfpv]      -    -    pem-
    4. “-n-“      [cdjsz]     -    -    pen-
    5. “-r-“      -       -    -   per-
    6. -       -       -    -   pe-
     */   
     if(preg_match('/^(peng)[aiueokghq]/',$kata)){ // 1.
      $__kata = preg_replace('/^(peng)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }    
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }    
     }
     
     if(preg_match('/^(peny)/',$kata)){ // 2.
      $__kata = preg_replace('/^(peny)/','s',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
     if(preg_match('/^(pem)[bfpv]/',$kata)){ // 3.
      $__kata = preg_replace('/^(pem)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }

      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
     if(preg_match('/^(pen)[cdjsz]/',$kata)){ // 4.
      $__kata = preg_replace('/^(pen)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
          /*-- Cek luluh -p ----------*/
      $__kata = preg_replace('/^(pem)/','p',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
     }
     if(preg_match('/^(per)/',$kata)){ // 5.    
      $__kata = preg_replace('/^(per)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
      /*-- Cek luluh -r ----------*/
      $__kata = preg_replace('/^(per)/','r',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
     if(preg_match('/^(pe)/',$kata)){ // 6.
      $__kata = preg_replace('/^(pe)/','',$kata);
      if($this->cekKamus($__kata)){   
       return $__kata; // Jika ada balik
      }
      $__kata__ = $this->Del_Derivation_Suffixes($__kata);
      if($this->cekKamus($__kata__)){
       return $__kata__;
      }
     }
    }
    /*------------end “pe-”, ---------------------------------------------*/
    /*------------ Awalan “memper-”, ---------------------------------------------*/
    
    if(preg_match('/^(memper)/',$kata)){    
     $__kata = preg_replace('/^(memper)/','',$kata);
     if($this->cekKamus($__kata)){   
      return $__kata; // Jika ada balik
     }
     $__kata__ = $this->Del_Derivation_Suffixes($__kata);
     if($this->cekKamus($__kata__)){
      return $__kata__;
     }
     /*-- Cek luluh -r ----------*/
     $__kata = preg_replace('/^(memper)/','r',$kata);
     if($this->cekKamus($__kata)){   
      return $__kata; // Jika ada balik
     }
     $__kata__ = $this->Del_Derivation_Suffixes($__kata);
     if($this->cekKamus($__kata__)){
      return $__kata__;
     }
    }  
    
   }
   /* --- Cek Ada Tidaknya Prefik/Awalan (“di-”, “ke-”, “se-”, “te-”, “be-”, “me-”, atau “pe-”) ------*/
   if(preg_match('/^(di|[kstbmp]e)/',$kata) == FALSE){
    return $kataAsal;
   }
   return $kataAsal;
  }
  public function Enhanced_CS($kata){
   // bisa ngambil id kategori dari form awal.
   //$Idkat    = addslashes($_POST['kategori']);   
   //echo "IdKategori = ".$Idkat . ";
   $kataAsal = $kata;

   /* 1. Cek Kata di Kamus jika Ada SELESAI */
   if($this->cekKamus($kata)){ // Cek Kamus
    return $kata; // Jika Ada kembalikan
   } 
   
    /*2. Buang Infection suffixes (\-lah", \-kah", \-ku", \-mu", atau \-nya") */
   $kata = $this->Del_Inflection_Suffixes($kata);
   
   /* 3. Buang Derivation suffix (\-i" or \-an") */
   $kata = $this->Del_Derivation_Suffixes($kata);
   
   /* 4. Buang Derivation prefix */
   $kata = $this->Del_Derivation_Prefix($kata); 
   return $kata;
  }
  public function _removekata($data){
           $stopWordRemover = new StopWordRemover();
           $text = $stopWordRemover->remove($data);
          return $text;
      }
  public function stem_list( $words ){
   $stemming = "";
   
         if (empty($words)) {
             return false;
         }
         //$results = array();         
             //$words = split("[ ,;\n\r\t\/:]+", trim($words));  
    $words = preg_split("/[^A-Za-z]+/", trim($words));
         foreach ( $words as $word ) {
    $stemming .= NAZIEF($word). ' ';
         }
   $gethasil =  _removekata($stemming );
   return $countFrecuency = frekuensi::countFrecuency($gethasil);
   //return $gethasil ;
  }
 }

 ?>