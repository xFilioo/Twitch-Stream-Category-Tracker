<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Change to your time zone
date_default_timezone_set('Europe/Warsaw');

$mysqli = new mysqli('your_hostname_here', 'your_username_here', 'your_password_here', 'your_database_name_here', 0000);
if ($mysqli->connect_errno) die("Connection error: " . $mysqli->connect_error);

$streamers = $mysqli->query("SELECT id, login FROM streamers WHERE login IN (
'neexcsgo',
'rybsonlol_',
'youngmulti',
'ewroon',
'h2p_gucio',
'xntentacion',
'slayproxx',
'delordione',
'xmerghani',
'mrdzinold',
'newmultishow',
'szelioficjalnie',
'cygan___',
'grendy',
'szzalony',
'nervarien',
'mamm0n',
'banduracartel',
'n3utr4lsf',
'overpow',
'tamae_senpai',
'maharadzza',
'bonkol',
'kubon_',
'nimuena_',
'kasix',
'angela35',
'demonzz1',
'gluhammer',
'achtenwlodar',
'graf',
'cygus134',
'worldofwarships',
'blackfireice',
'spartiatix',
'rallencs2',
'discokarol',
'natanzgorzyk',
'nieuczesana',
'parisplatynov',
'besi523',
'pago3',
'lukisteve',
'katbabis',
'pr0tzyq',
'awizopocztowe',
'darkocsgo',
'pevor13',
'remsua',
'arquel',
'kangurek_kao_pej',
'nexe_',
'pyka97',
'sevel07',
'diables',
'tubson_',
'blachu_',
'officialhyper',
'ortis',
'tojadenis',
'dorotka22_',
'randombrucetv',
'xth0rek',
'monsteryoo',
'tuttekhs',
'vysotzky',
'xtom223',
'piotrmaciejczak',
'cinkrofwest',
'lotharhs',
'zwierzaczunio',
'bruz777',
'keremekesze',
'stompcsgo',
'menders_7',
'chopo_kaify',
'jaskol95',
'spzoomario',
'bykmateo',
'revo_toja',
'xemtek',
'mokrysuchar',
'1wron3k',
'putrefy',
'papapawian',
'chesscompl',
'maxigashi',
'alanzqtft',
'matiskaterryfl',
'haemhype',
'qreii',
'masle1',
'ospanno',
'izakooo',
'kamileater',
'pirlo444',
'lala_style',
'kondyss',
'crownycro',
'patrycjusz'
) ORDER BY login")->fetch_all(MYSQLI_ASSOC);

$selected = $_GET['streamer'] ?? $streamers[0]['login'];
$streamer = $mysqli->query("SELECT id, login FROM streamers WHERE login='$selected'")->fetch_assoc();
$streamer_id = $streamer['id'];

$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$end_datetime = $end . ' 23:59:59';

$now = date('Y-m-d H:i:s');

$stmt = $mysqli->prepare("
  SELECT c.name,
    SUM(
      GREATEST(
        0,
        TIMESTAMPDIFF(
          MINUTE,
          GREATEST(s.start_time, ?),
          LEAST(COALESCE(s.end_time, ?), ?)
        )
      )
    ) as minutes
  FROM streams s
  JOIN categories c ON s.category_id = c.id
  WHERE s.streamer_id = ?
    AND GREATEST(s.start_time, ?) < LEAST(COALESCE(s.end_time, ?), ?)
  GROUP BY c.id, c.name
  ORDER BY minutes DESC
");
$stmt->bind_param(
  'sssisss',
  $start,
  $now,
  $end_datetime,
  $streamer_id,
  $start,
  $now,
  $end_datetime
);

$stmt->execute();
$stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$total_minutes = array_sum(array_column($stats, 'minutes'));

$summary = @json_decode(@file_get_contents("https://twitchtracker.com/api/channels/summary/$selected"), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Twitch Category Stats</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header>
  <h1><span class="logo">Twitch Category Stats</span></h1>
  <form method="get" id="filters">
    <label>Streamer:
      <select name="streamer" onchange="document.getElementById('filters').submit()">
        <?php foreach($streamers as $s): ?>
          <option value="<?= $s['login'] ?>" <?= $selected==$s['login']?'selected':'' ?>><?= ucfirst($s['login']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>From: <input type="date" name="start" min="2025-07-08" value="<?= $start ?>"></label>
    <label>To: <input type="date" name="end" value="<?= $end ?>"></label>
    <button type="submit">Show</button>
  </form>
  <?php if ($summary): ?>
  <div class="summary">
    <div><span>Rank:</span> <?= $summary['rank'] ?></div>
    <div><span>Minutes streamed:</span> <?= $summary['minutes_streamed'] ?></div>
    <div><span>Avg. viewers:</span> <?= $summary['avg_viewers'] ?></div>
    <div><span>Max viewers:</span> <?= $summary['max_viewers'] ?></div>
    <div><span>Hours watched:</span> <?= $summary['hours_watched'] ?></div>
    <div><span>New follows:</span> <?= $summary['followers'] ?></div>
    <div><span>Total follows:</span> <?= $summary['followers_total'] ?></div>
  </div>
  <?php endif; ?>
</header>
<main>
  <h2>Stream categories <span class="datetitle">(<?= $start ?> – <?= $end ?>)</span></h2>
  <div class="switcher">
    <button type="button" id="showTable" class="active">Table</button>
    <button type="button" id="showChart">Chart</button>
  </div>
  <div id="tableWrap">
    <table id="categories" class="active">
      <thead>
        <tr>
          <th onclick="sortTable(0)">Category</th>
          <th onclick="sortTable(1)">Minutes</th>
          <th onclick="sortTable(2)">% of time</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($stats as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= $row['minutes'] ?></td>
            <td><?= $total_minutes ? round(100*$row['minutes']/$total_minutes,1) : 0 ?>%</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (empty($stats)): ?>
      <div class="nodata">No data in the selected range.</div>
    <?php endif; ?>
  </div>
  <div id="chartWrap">
    <canvas id="chart"></canvas>
  </div>
</main>

  <?php include 'footer.html'; ?>
  <script>
    const labels = <?= json_encode(array_column($stats, 'name')) ?>;
    const data = <?= json_encode(array_column($stats, 'minutes')) ?>;

    let chart = new Chart(document.getElementById('chart'), {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Minutes streamed',
          data: data,
          backgroundColor: 'rgba(59,130,246,0.8)',
          borderRadius: 6,
          maxBarThickness: 50
        }]
      },
      options: {
        plugins: { 
          legend: { display: false },
          tooltip: { backgroundColor: '#23232b', titleColor: '#fff', bodyColor: '#fff' }
        },
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { ticks: { color: '#fff', font: {size: 14} }, grid: { color: '#333' } },
          y: { ticks: { color: '#fff', font: {size: 14} }, grid: { color: '#333' }, beginAtZero: true }
        }
      }
    });

    const showTableBtn = document.getElementById('showTable');
    const showChartBtn = document.getElementById('showChart');
    const tableWrap = document.getElementById('tableWrap');
    const chartWrap = document.getElementById('chartWrap');
    showTableBtn.onclick = function() {
      showTableBtn.classList.add('active');
      showChartBtn.classList.remove('active');
      tableWrap.style.display = 'block';
      chartWrap.style.display = 'none';
    };
    showChartBtn.onclick = function() {
      showTableBtn.classList.remove('active');
      showChartBtn.classList.add('active');
      tableWrap.style.display = 'none';
      chartWrap.style.display = 'block';
    };

    tableWrap.style.display = 'block';
    chartWrap.style.display = 'none';

    function sortTable(n) {
      var table = document.getElementById("categories"), rows, switching = true, dir = "desc", switchcount = 0;
      while (switching) {
        switching = false; rows = table.rows;
        for (var i = 1; i < (rows.length - 1); i++) {
          var x = rows[i].getElementsByTagName("TD")[n], y = rows[i + 1].getElementsByTagName("TD")[n], shouldSwitch = false;
          var xVal = isNaN(Number(x.innerHTML)) ? x.innerHTML.toLowerCase() : Number(x.innerHTML);
          var yVal = isNaN(Number(y.innerHTML)) ? y.innerHTML.toLowerCase() : Number(y.innerHTML);
          if ((dir == "asc" && xVal > yVal) || (dir == "desc" && xVal < yVal)) {
            shouldSwitch = true; break;
          }
        }
        if (shouldSwitch) {
          rows[i].parentNode.insertBefore(rows[i + 1], rows[i]); switching = true; switchcount++;
        } else if (switchcount == 0 && dir == "desc") { dir = "asc"; switching = true; }
      }
    }
  </script>
</body>
</html>
