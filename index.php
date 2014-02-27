<!DOCTYPE html>
<?php
/**
 * FCU-ics-Class-Schedule: Make .ics format class Schedule
 *
 * @package FCU-ics-Class-Schedule
 * @author Lea <lea@it-easy.tw>
*/

/** Config */
$semester = 1022; //學期
$sd = 20140213; //開始上課日期

// 定義每節課時間、星期對應
$t['01'][0] = '0810';
$t['02'][0] = '0910';
$t['03'][0] = '1010';
$t['04'][0] = '1110';
$t['05'][0] = '1210';
$t['06'][0] = '1310';
$t['07'][0] = '1410';
$t['08'][0] = '1510';
$t['09'][0] = '1610';
$t['10'][0] = '1710';
$t['11'][0] = '1810';
$t['12'][0] = '1910';
$t['13'][0] = '2010';
$t['14'][0] = '2110';

$t['01'][1] = '0900';
$t['02'][1] = '1000';
$t['03'][1] = '1100';
$t['04'][1] = '1200';
$t['05'][1] = '1300';
$t['06'][1] = '1400';
$t['07'][1] = '1500';
$t['08'][1] = '1600';
$t['09'][1] = '1700';
$t['10'][1] = '1800';
$t['11'][1] = '1900';
$t['12'][1] = '2000';
$t['13'][1] = '2100';
$t['14'][1] = '2200';

$wd = array(
    "一" => "MO",
    "二" => "TU",
    "三" => "WE",
    "四" => "TH",
    "五" => "FR",
    "六" => "SA",
    "日" => "SU"
);

$d = array(
  "MO" => "1",
  "FR" => "5",
  "TU" => "2",
  "SA" => "6",
  "WE" => "3",
  "SU" => "7",
  "TH" => "4"
);

/** 刪除過期檔案　*/
if ($handle = opendir('./ics')) {
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != "..") {
            $ftime = filemtime('./ics/'.$file);
            if(time()-$ftime > 1800){ //超過30分鐘
                unlink('./ics/'.$file);
            }
        }
    }   
}

/** exe */
if ($_POST['press']) {
  /** get data */
  $sdu = strtotime($sd);
  $swd = date('w', $sdu);  // 轉換開始上課日期
  $courses = $_POST['content'];

  if ($courses == "" || $courses == null) {
  	$error_no_data = 1;
  	$_POST['press'] = "";
  } else {
    $courses = explode("\n", $courses);
    $data_empty = TRUE; //是否沒有有效資料
    foreach ($courses as $cn => $course){
        $course = explode("\t", $course);
        if (!preg_match('/^[A-Z0-9]+ .*$/', $course[1])){ //檢查科目名稱格式 EX: GBHC111 文明史
            //get error
            continue;
        }
        $course_info = explode(" ", $course[4]); //課程上課時間/地點/教師
        $length = count($course_info);
        $course_info['title'] = $course[1];
        $course_info['lecturer'] = $course_info[$length-1];
        if($length%2 == 0){
           $course_info['lecturer'] = "";            
        }
        else{
            $length--;
        }
        for($x=0;$x<$length;$x=$x+2){ //檢查上課時間格式
            if (preg_match('/^\((.{3})\)([0-9]{2})$/',$course_info[$x],$matches)){ //單堂 $mathes[1]為星期 $matches[2]為節次        
                $course_info['class'][$x/2]['illegal'] = FALSE;
                $data_empty = FALSE;
                $course_info['class'][$x/2]['location'] = $course_info[$x+1];
                $course_info['class'][$x/2]['weekday'] = $wd[$matches[1]];
                $course_info['class'][$x/2]['start'] = $t[$matches[2]][0];
                $course_info['class'][$x/2]['end'] = $t[$matches[2]][1];
                                   
            }
            else if (preg_match('/^\((.{3})\)([0-9]{2})-([0-9]{2})$/',$course_info[$x],$matches)){ //非單堂 $matches[2]為開始節次 $matches[3]為結束節次
                $course_info['class'][$x/2]['illegal'] = FALSE;   
                $data_empty = FALSE;      
                $course_info['class'][$x/2]['location'] = $course_info[$x+1];  
                $course_info['class'][$x/2]['weekday'] = $wd[$matches[1]];
                $course_info['class'][$x/2]['start'] = $t[$matches[2]][0];
                $course_info['class'][$x/2]['end'] = $t[$matches[3]][1];             
            }
            else{
                $course_info['class'][$x/2]['illegal'] = TRUE;
                continue;
            }
            $merged_data[$cn] = $course_info;
        }
    }
    
    if ($data_empty) {
      $has_error = true;
      $error_type = 'no_data';
    }
    
    /** print data */
    $fileKey = md5(serialize($courses));
    $file = "ics/".$semester."-".$fileKey.".ics";
    
    $handle= fopen($file,'w');
    $txt = "BEGIN:VCALENDAR\nPRODID:fun.it-easy.tw\nVERSION:2.0\nCALSCALE:GREGORIAN\nMETHOD:PUBLISH\nX-WR-CALNAME:課程表\nX-WR-TIMEZONE:Asia/Taipei\nX-WR-CALDESC:\nBEGIN:VTIMEZONE\nTZID:Asia/Taipei\nX-LIC-LOCATION:Asia/Taipei\nBEGIN:STANDARD\nTZOFFSETFROM:+0800\nTZOFFSETTO:+0800\nTZNAME:CST\nDTSTART:19700101T000000\nEND:STANDARD\nEND:VTIMEZONE";
    fwrite($handle,$txt);
    foreach ($merged_data as $course_inf) {
        foreach ($course_inf['class'] as $cn => $dat) {
            if(!$course_info['class'][$x/2]['illegal']){
                $weekday = $dat['weekday'];
                $daysafter = $d[$weekday]-$swd;          
                if ($daysafter < 0) $daysafter+=7;

                $eyears = date("Y", $sdu);
                $emonths = date("m", $sdu);
                $edays = date("d", $sdu);
                $edate = date("Ymd", mktime(0,0,0,$emonths,$edays+$daysafter,$eyears));
                preg_match('/^[0-9A-Z]+ (.*)$/',$course_inf['title'],$matches);
                $title = $matches[1];

                $txt = "\n\nBEGIN:VEVENT\n";
                fwrite($handle,$txt);
            
                $txt = "DTSTART;TZID=Asia/Taipei:".$edate."T".$dat['start']."00\n";
                fwrite($handle,$txt);

                $txt = "DTEND;TZID=Asia/Taipei:".$edate."T".$dat['end']."00\n";
                fwrite($handle,$txt);

                $txt = "RRULE:FREQ=WEEKLY;COUNT=18;BYDAY=".$weekday."\n";
                fwrite($handle,$txt);

                $txt = "SUMMARY:".$title."\nLOCATION:".$course_inf['class'][$cn]['location']."\nDESCRIPTION:授課教師: ".$course_inf['lecturer']."\n";
                fwrite($handle,$txt);

                $txt = "END:VEVENT\n";
                fwrite($handle,$txt);
            }
           
        }
    }

    $txt = "END:VCALENDAR";
    fwrite($handle,$txt);
    fclose($handle);
  
  }
}
?>

<html lang="zh-tw">
  <head>
    <meta charset="utf-8">
    <title>FCU 課表行事曆製作工具</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="把逢甲課表快速排進行事曆的工具，支援 iOS、Android、Mac 以及絕大多數行事曆軟體。會自動把上課地點和授課教師附註上去。">
    <meta name="author" content="Lea">
    <meta property="og:image" content="img/how0-mac-2.jpg">
    <meta property="og:image" content="img/how0-ios-3.jpg">

    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <style type="text/css">
      body {
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #f5f5f5;
      }

      label {
      	font-size: 16px;
      	margin-top: 20px;
      }

      input {
      	margin-top: 0 !important;
      	margin-bottom: 0 !important;
      }

      .form-signin {
        max-width: 380px;
        padding: 19px 29px 29px;
        margin: 0 auto 20px;
        background-color: #fff;
        border: 1px solid #e5e5e5;
        -webkit-border-radius: 5px;
           -moz-border-radius: 5px;
                border-radius: 5px;
        -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
           -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
                box-shadow: 0 1px 2px rgba(0,0,0,.05);
      }
      .form-signin-heading {
        font-size: 31.5px;
      }
      .form-signin .form-signin-heading,
      .form-signin .checkbox {
        margin-bottom: 10px;
      }
      .form-signin input[type="text"],
      .form-signin input[type="password"] {
        font-size: 16px;
        height: auto;
        margin-bottom: 15px;
        padding: 7px 9px;
      }
      .control-label {
        margin-left : 18px;
        text-indent : -18px ;
      }
      .btn {
        margin-bottom: 12px;
      }
      .modal-header {
      }
      .modal-body .nav-tabs {
        margin-bottom: 0px;
      }
      .modal-footer .btn {
        margin-bottom: 0px;
      }
      .accordion-heading {
        background-color: #f5f5f5;
      }
      .accordion-toggle {
        color: black;
      }
      .data-group, .data-heading, .data-collapse, .data-heading > *, .data-collapse > * {
        background-color: white;
        padding: 0 !important;
        border: 0 !important;
      }
      .load {
        -webkit-transition: 1s;
           -moz-transition: 1s;
             -o-transition: 1s;
                transition: 1s;
      }
      .then_what, .then_what * {
        text-align: right;
        color: #888;
      }
      .then_what a {
        text-decoration: underline;
      }
      @media (max-width: 979px) {
        body {
          padding-top: 24px !important;
        }
      }
    </style>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link href="favicon.png" rel="image_src" type="image/jpeg">
    <link href="css/bootstrap-responsive.css" rel="stylesheet">
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
       
  </head>
  <body>
    <?php include_once("../analyticstracking.php") // google analytics ?> 
    <div id="fb-root"></div>
    <script>(function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/zh_TW/all.js#xfbml=1&appId=132913846761101";
        fjs.parentNode.insertBefore(js, fjs);
      }(document, 'script', 'facebook-jssdk'));</script>
    <div class="container">
      <form action="#get" class="form-signin" method="post" onsubmit="return validate_form(this);">
      <h1 class="form-signin-heading">FCU <br>課表行事曆製作工具 <small><?php echo $semester; ?></small></h1>
        <div class="accordion-group">
          <div class="accordion-heading">
            <a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">
              <i class="icon-question-sign"></i> 這啥？
            </a>
          </div>
          <div id="collapseOne" class="accordion-body collapse" style="height: 0px;">
            <div class="accordion-inner">
              <p>把逢甲課表快速排進行事曆的工具。會自動把上課地點和授課教師附註上去。
              <a href="#how0" data-toggle="modal">詳細</a>。</p>
              <p>有鑒於把課表一個一個手動 key-in 到行事曆會死掉所以做了這個東西。支援 iOS、Android、Mac 以及絕大多數可匯入 .ics 格式的行事曆軟體。</p>
              <p>
                <iframe src="http://ghbtns.com/github-btn.html?user=MagicLea&repo=FCU-ics-Class-Schedule&type=watch" allowtransparency="true" frameborder="0" scrolling="0" width="62" height="22"></iframe> 
                <iframe src="http://ghbtns.com/github-btn.html?user=MagicLea&repo=FCU-ics-Class-Schedule&type=fork" allowtransparency="true" frameborder="0" scrolling="0" width="55" height="22"></iframe> 
              </p>

            </div>
          </div>
        </div>

        <hr>

        <div class="control-group content">
          <label class="control-label" for="content">1. 輸入您的選課一覽表。<br>進 <a href="http://sdsweb.oit.fcu.edu.tw/coursequest/login.jsp" target="_blank">課程檢索系統</a>並查詢「選課一覽表」，把選課資料全部複製貼上來。<a href="#how1" data-toggle="modal">圖。</a></label>
            <textarea name="content" id="content"  class="input-block-level" placeholder="" style="height: 100px;"><?php echo $_POST['content']; ?></textarea>
        </div>

        <label for="press">2.</label>
        <input type="submit" name="press" value="按下去" id="press" class="btn btn-large btn-block" <?php if($_POST['press']) echo "disabled=\"disabled\""; ?> >

        <div class="load" style="<?php if(!$_POST['press']) echo "height: 0;"; ?> overflow: hidden;">
          <label>3. 等</label>
          <div class="progress progress-info <?php if(!$_POST['press']) echo "progress-striped active"; ?>">
            <div class="bar" style="width: 100%;"></div>
          </div>
        </div>

        <?php
        if ($has_error) {
          switch ($error_type) {
            case 'no_data':
            echo '<div class="alert alert-block alert-error"><button type="button" class="close" data-dismiss="alert">×</button><h4 class="alert-heading">沒有課啊！</h4><p>找不到課程代碼片段。請確認您貼上的文字包含課程代碼，且以空白分隔。<br><a href="?">重來</a></p></div>';
              break;
            default:
              echo '<div class="alert alert-block alert-error"><button type="button" class="close" data-dismiss="alert">×</button><h4 class="alert-heading">有誤。</h4><br><a href="?">重來</a></div>';
              break;
          }
        }
        if (!$_POST['press'] || $has_error) echo "<!--";
        ?>

        <label id="get" for="get">4.</label>
        <a id="get" class="btn btn-large btn-block btn-primary" href="<?php echo "ics/".$semester."-".$fileKey.".ics"; ?>">取得日曆</a>
        <div class="then_what">(<a href="#how0" data-toggle="modal">然後呢?</a>)</div>
        <center>
          <br>
          <a class="collapsed" data-toggle="collapse" data-parent="#accordion2" href="#collapsedata">顯示數據</a>
          |
          <a href="?">再來一次</a>
        </center>

        <div class="accordion-group data-group">
          <div id="collapsedata" class="accordion-body collapse data-collapse" style="height: 0px;">
            <div class="accordion-inner">
              <br>
              <pre>
<?php echo ereg_replace("  ", " ", print_r($merged_data, true)); ?>
              </pre>
            </div>
          </div>
        </div>

        <?php if(!$_POST['press'] || $has_error) echo "-->"; ?>
        <hr>        
        <div class="fb-like" data-href="http://fun.it-easy.tw/FCU-ics-Class-Schedule/" data-layout="standard" data-action="like" data-show-faces="true" data-share="true"></div>
      </form>
      <div><center>本工具修改自<a href="http://cal.ntust.co/" target="_blank">NTUST 課表行事曆製作工具</a> :D </center></div>
      <script type="text/javascript">
        function loadbar(){
        }
        $(".id").keydown(function(){
          $(".id").removeClass("error");
        });
        $(".content").keydown(function(){
          $(".content").removeClass("error");
        });
      </script>
    </div>

<!-- Modal -->
    <div id="how0" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3>用法</h3>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs">
          <li class="active"><a href="#how0_ios" data-toggle="tab">iOS</a></li>
          <li><a href="#how0_android" data-toggle="tab">Android</a></li>
          <li><a href="#how0_mac" data-toggle="tab">Mac</a></li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade active in" id="how0_ios">
            <h3>iOS</h3>
            <hr style="margin-top: 0; margin-bottom: 10px;">
            <p>直接在 iPhone/iPad 上開啟此網頁。</p>
            <img src="img/how0-ios.jpg"><br><br>
            <p>建議加入到新行事曆，以免混亂您原有的行事曆。</p>
            <img src="img/how0-ios-2.jpg"><br><br>
            <img src="img/how0-ios-3.jpg">
          </div>
          <div class="tab-pane fade" id="how0_android">
            <h3>Android</h3>
            <hr style="margin-top: 0; margin-bottom: 10px;">
            <p>前往 <a href="https://www.google.com/calendar/" target="_blank">https://www.google.com/calendar/</a>，匯入日曆檔案到 Google Calender，再與 Android 同步。</p>
            <img src="img/how0-android.jpg">
          </div>
          <div class="tab-pane fade" id="how0_mac">
            <h3>Mac</h3>
            <hr style="margin-top: 0; margin-bottom: 10px;">
            <p>直接按兩下，用行事曆打開它。建議匯入到新行事曆，以免混亂您原有的行事曆。</p>
            <img src="img/how0-mac.jpg">
            <img src="img/how0-mac-2.jpg">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn" data-dismiss="modal">　好　</a>
      </div>
    </div>
    <div id="how1" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel">　</h3>
      </div>
      <div class="modal-body">
        <img src="img/how1.png">   
      </div>
      <div class="modal-footer">
        <a href="#" class="btn" data-dismiss="modal">　好　</a>
      </div>
    </div>
    <script type="text/javascript">
      function validate_required(field, c) {
        with (field) {
          if (value == null || value == "") {
            $(c).addClass("error");
            $(c).addClass("animated");
            $(c).addClass("shake");
            setTimeout('$(".control-group").removeClass("shake")', 1000);
            return false;
          }else{
            return true;
          }
        }
      }

      function validate_form(thisform) {
        with (thisform) {
          if (validate_required(content, ".content") == false) {
            content.focus();
            return false
          }
          $(".load").css("height","auto");
        }
        return true;
      }
    </script>
  </body>
</html>
