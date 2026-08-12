<?php
declare(strict_types=1);
if (isset($_GET['service-worker'])) {
  header('Content-Type: application/javascript; charset=utf-8');
  header('Cache-Control: no-cache');
  echo <<<'JS'
const CACHE="job-records-shell-v8";
const SHELL=["./","index.html","assets/style.css","assets/app.js","assets/manifest.json","assets/icon.svg"];
self.addEventListener("install",e=>e.waitUntil(caches.open(CACHE).then(c=>c.addAll(SHELL)).then(()=>self.skipWaiting())));
self.addEventListener("activate",e=>e.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim())));
self.addEventListener("fetch",e=>{const u=new URL(e.request.url);if(u.pathname.endsWith("/api.php")||e.request.method!=="GET")return;e.respondWith(fetch(e.request).then(r=>{const copy=r.clone();caches.open(CACHE).then(c=>c.put(e.request,copy));return r}).catch(()=>caches.match(e.request).then(r=>r||caches.match("index.html"))))});
self.addEventListener("notificationclick",e=>{e.notification.close();e.waitUntil(clients.matchAll({type:"window",includeUncontrolled:true}).then(list=>{for(const c of list){if("focus" in c)return c.focus()}return clients.openWindow("./")}))});
JS;
  exit;
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
$file = __DIR__ . '/records.json';
function respond(int $status, array $payload): never { http_response_code($status); echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function clean($value, int $max = 500): string { return mb_substr(trim(strip_tags((string)$value)), 0, $max); }
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $handle = @fopen($file, 'c+');
  if (!$handle) respond(500, ['error' => 'Unable to open the record database.']);
  if (!flock($handle, LOCK_SH)) { fclose($handle); respond(503, ['error' => 'Database is busy.']); }
  rewind($handle); $raw = stream_get_contents($handle); flock($handle, LOCK_UN); fclose($handle);
  $records = json_decode($raw ?: '[]', true);
  if (!is_array($records)) respond(500, ['error' => 'The record database is invalid.']);
  respond(200, ['records' => $records]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Allow: GET, POST'); respond(405, ['error' => 'Method not allowed.']); }
$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input) || !isset($input['records']) || !is_array($input['records'])) respond(400, ['error' => 'Expected a records array.']);
$clean = [];
foreach ($input['records'] as $record) {
  if (!is_array($record)) respond(422, ['error' => 'Each record must be an object.']);
  $date = clean($record['date'] ?? '', 10); $location = clean($record['location'] ?? '', 200); $invoice = clean($record['invoice'] ?? '', 100); $paid = filter_var($record['paid'] ?? false, FILTER_VALIDATE_BOOLEAN); $id = clean($record['id'] ?? '', 100); $cash = filter_var($record['cash'] ?? null, FILTER_VALIDATE_FLOAT);
  $validDate = DateTime::createFromFormat('!Y-m-d', $date); $validDate = $validDate && $validDate->format('Y-m-d') === $date;
  if (!$id || !$validDate || !$location || !$invoice || $cash === false || $cash < 0) respond(422, ['error' => 'Every record needs a valid date, location, invoice number, and non-negative cash amount.']);
  $clean[] = ['id'=>$id, 'date'=>$date, 'location'=>$location, 'invoice'=>$invoice, 'cash'=>number_format((float)$cash, 2, '.', ''), 'paid'=>$paid];
}
$json = json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) respond(500, ['error' => 'Unable to encode records.']);
$handle = @fopen($file, 'c+');
if (!$handle) respond(500, ['error' => 'The database is not writable.']);
if (!flock($handle, LOCK_EX)) { fclose($handle); respond(503, ['error' => 'Database is busy.']); }
rewind($handle); if (!ftruncate($handle, 0) || fwrite($handle, $json) === false || !fflush($handle)) { flock($handle, LOCK_UN); fclose($handle); respond(500, ['error' => 'Unable to save records.']); }
flock($handle, LOCK_UN); fclose($handle); respond(200, ['success' => true, 'count' => count($clean)]);
