<?php


//서버 제목 가져오기 (HTML)
$URL = "https://status.kuma.pet"; //Uptime Kuma 상태페이지 주소를 입력해주세요!



$content = file_get_contents($URL);

//상태페이지 설정 가져오기
function padgeconfig($content, $config) {

  preg_match('/window\.preloadData\s=\s(.*?);/s', $content, $matches);

  if (isset($matches[1])) {
      $result = str_replace("'", '"', $matches[1]); 
      $data = json_decode($result, true);  // true 옵션으로 설정하면 연관 배열로 반환합니다.
      return $data['config'][$config];
    
  } else {
      return "Data not found.";
  }

}

//서버 상태 정보 가져오기 (JSON)
$json = file_get_contents($URL. "/api/status-page/heartbeat/" . padgeconfig($content, 'slug'));


//json 데이터 배열로 변환
$data = json_decode($json, true);



//서버 ID 불러오기
$serverdata = json_decode($json);

$serverList = array();

foreach ($serverdata->uptimeList as $key => $servervalue) {

  if (preg_match('/^\d+_24$/', $key)) {
    $serverList[$key] = $servervalue;
  }
}
//서버 ID 배열로 변환
$keys = array_keys($serverList);

//응답시간 서버 선택
$serverpingList = str_replace("_24", "", $keys);



//서버상태 요약표시
$online_count = 0;
$offline_count = 0;
$warning_count = 0;
$maintenance_count = 0;
$unknown_count = 0;

foreach ($data['heartbeatList'] as $server) {
  switch ($server[0]['status']) {
    case 0:
      $offline_count++;
      break;
    case 1:
      $online_count++;
      break;
    case 2:
      $warning_count++;
      break;
    case 3:
      $maintenance_count++;
      break;
    default:
      $unknown_count++;
      break;
  }
}




//서버 업타임 표시 함수
function uptime($data, $svrid)  {
  if (isset($data['uptimeList'])) {
    $uptimeList = json_encode($data['uptimeList']);
    $data = json_decode($uptimeList, true);
    $value = round($data[$svrid] * 100, 2);
    echo $value . "%";
  } else {
    echo "No Data";
  }
}


//최근 서버 응답시간 표시 함수
function latestping($data, $svrid)  {
  if (isset($data['heartbeatList'][$svrid])) {
    $ping = $data['heartbeatList'][$svrid];
    $latestPing = end($ping)['ping'];
    if ($latestPing == "null") {
      echo "Down";
    } else {
      echo $latestPing;
    }
  } else {
    echo "No Data";
  }
}

//상태페이지에 서버 명 가져오기
function servertitle($svrid, $content) {

  preg_match('/window\.preloadData\s=\s(.*?);/s', $content, $matches);

  if (isset($matches[1])) {
      $result = str_replace("'", '"', $matches[1]); 
      $data = json_decode($result, true);  // true 옵션으로 설정하면 연관 배열로 반환합니다.

      foreach ($data['publicGroupList'] as $group) {
          foreach ($group['monitorList'] as $monitor) {
              if ($monitor['id'] === $svrid) {
                  echo $monitor['name'];
                  break;
              }
          }
      }
    
  } else {
      echo "Data not found.";
  }

}









?>
<!DOCTYPE html>
<html lang="ko">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Server Status</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <div class="app-container">
    <div class="app-header">
<a href="https://www.searchai.me/">
          <p class="app-name"><img src="<?php echo $URL . padgeconfig($content,'icon');?>"></p>
          <p class="app-name"> <?php echo padgeconfig($content,'title');?></p>
        </a>

    </div>
    <div class="app-content">
      <div class="projects-section">
        <div class="projects-section-header">
          <p>Service Status</p>
          <p class="time"> <?php echo date('M, d'); ?> </p>
        </div>
        <div class="projects-section-line">
          <div class="projects-status">
            <div class="item-status"><span class="status-number"><?php echo $online_count; ?></span><span class="status-type" style="color:#f0f0f0">Up</span></div>
            <div class="item-status"><span class="status-number"><?php echo $offline_count; ?></span><span class="status-type" style="color:#ff534b">Down</span></div>
            <div class="item-status"><span class="status-number"><?php echo $warning_count; ?></span><span class="status-type" style="color:#fae000">Warning</span></div>
            <div class="item-status"><span class="status-number"><?php echo $maintenance_count; ?></span><span class="status-type" style="color:#6093ff">Maintence</span></div>
            <div class="item-status"><span class="status-number"><?php echo $unknown_count; ?></span><span class="status-type" style="color:#d2d2d2">Unknown</span></div>
          </div>
        </div>
        <div class="project-boxes" id="project-boxes">
        <?php
          for ($i = 0; $i <= count($serverpingList) - 1; $i++) {
          ?>
              <div class="project-box-wrapper">
                  <div class="project-box" style="background-color:#d5deff">
                      <div class="project-box-header"><span>Service</span></div>
                      <div class="project-box-content-header">
                        <p class="box-content-header"><?php servertitle((int)str_replace("_24", "", $keys[$i]), $content);?></p>
                  </div>
                      <div class="box-progress-wrapper">
                          <p class="box-progress-header">Uptime</p>
                          <div class="box-progress-bar"><span class="box-progress" style="width: <?php uptime($data, $keys[$i], $content); ?>;background-color:#4067f9"></span></div>
                          <p class="box-progress-percentage"> <?php uptime($data, $keys[$i]); ?> </p>
                      </div>
                      <div class="project-box-footer">
                      <div class="days-left" style="color:#4067f9"> <?php latestping($data, $serverpingList[$i]); ?>ms</div>
                     </div>  
                  </div> 
              </diV>
          
          <?php
          }
          ?>
        </div>
      </div>
    </div>
  </div>
  <script>
    const container = document.getElementById("project-boxes");

    function checkWidth() {
      if (window.innerWidth <= 945) {
        container.classList.remove("jsGridView");
        container.classList.add("jsListView");
      } else {
        container.classList.remove("jsListView");
        container.classList.add("jsGridView");
      }
    }
    checkWidth();
    window.addEventListener('resize', checkWidth);
  </script>
</body>

</html>