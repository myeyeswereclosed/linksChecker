<?php
// Рекурсивное получение всех файлов, соотвествующих $pattern
// из папки $dir и ее подпапок
function getFiles($dir, $pattern) {
	static $filelist;
	$handle = opendir($dir);
	while (($obj = readdir($handle)) !== false) {
		if ($obj =='.' || $obj == '..') {
			continue;
		} else {
			if (preg_match($pattern, $obj))
				$filelist[] = $dir . '\\' . $obj;
		}

		if (is_dir($dir . '\\' . $obj)) {
			getFiles($dir . '\\' . $obj, $pattern);
		}			
	}
	closedir($handle);
	return $filelist;
}

// Из массива файлов возвращаются все возможные сслыки на внешние страницы
function getLinksFromFiles(array $files) {
	$fileHtmlCode = array();
	$links = array();

	foreach ($files as $file) {
		$fileHtmlCode[] = file_get_contents($file);
	}

	foreach ($fileHtmlCode as $num => $content) {
		preg_match_all('/<a.*?href=\"https?:\/\/(.+?)\">.*?<\/a>/U', $content, $matches[$num], PREG_SET_ORDER);
	}

	foreach ($matches as $key => $array) {
		if (!empty($array)) {
			foreach ($array as $num => $captures) {
				$start = strpos($captures[1], "\""); 
				$links[] = ($start > 0) ? substr($captures[1], 0, $start) : $captures[1]; 
			}
		}
	}
	return array_values(array_unique($links));
}

// Определяет тип URL, запрашивая страницу из массива $links
function determineUrls(array $links) {
	$urls = array();

	$curlOptions = array(
	CURLOPT_HEADER => true,
	CURLOPT_NOBODY => true,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_ENCODING => "",
	CURLOPT_AUTOREFERER => true,
	CURLOPT_CONNECTTIMEOUT => 120,
	CURLOPT_TIMEOUT => 120,
	CURLOPT_MAXREDIRS => 10
	);

	foreach ($links as $url) {
		$ch = curl_init($url);
		curl_setopt_array($ch, $curlOptions);
		curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if (!$httpCode)
			$urls['deadUrls'][] = $url;
		elseif ($httpCode == '404')
			$urls['notFoundUrls'][] = $url;
		else
			$urls['workUrls'][] = $url;
	}

	return $urls; 
}

getFiles(__DIR__, '/.html/');
$webFiles = getFiles(__DIR__, '/.php/');

$links = getLinksFromFiles($webFiles);

$urls = determineUrls($links);
echo "<pre>";
print_r($urls);
echo "</pre>";

?>


