<?php

class Aoe_Profiler_Helper_Data extends Mage_Core_Helper_Abstract {

	const XML_PATH_PROFILE_DIR = 'dev/debug/profileDir';

	public function format_time($duration, $precision=0) {
		return round($duration * 1000, $precision);
	}

	public function format_realmem($bytes) {
		return $this->format_emalloc($bytes);
	}

	public function format_emalloc($bytes) {
		$res =	number_format($bytes / (1024 * 1024), 2);
		if ($res == '-0.00') {
			$res = '0.00'; // looks silly otherwise
		}
		return $res;
	}

	/**
	 * Get skin file content
	 *
	 * @param $file
	 * @return string
	 */
	public function getSkinFileContent($file) {
		$package = Mage::getSingleton('core/design_package');
		$areaBackup = $package->getArea();
		$path = $package
			->setArea('frontend')
			->getFilename($file, array('_type' => 'skin'));
		$content = file_get_contents($path);
		$package->setArea($areaBackup);
		return $content;
	}

	/**
	 * Renders Magento page with profiler output to file
	 * Useful when profiling cli scripts
	 *
	 * @param string $title
	 * @return string|bool The filename where the profile data was stored or false if there was an error.
	 */
	public function renderProfilerOutputToFile($title = 'Aoe_Profiler') {
		// Disable further profiling
		Varien_Profiler::disable();

		// Render HTML
		Mage::getDesign()->setArea('frontend');
		Mage::app()->getLayout()->setArea('frontend');
		$profilerBlock = Mage::app()->getLayout()->createBlock('core/profiler');
		$profilerBlock->setTitle($title);
		$profilerBlock->setForceRender(TRUE);
		$content = <<<HTML
<html>
<head>
    <title>$title</title>
    <script src="https://ajax.googleapis.com/ajax/libs/prototype/1.7.3.0/prototype.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/scriptaculous.js"></script>
</head>
<body>
{$profilerBlock->toHtml()}
</body>
</html>
HTML;

		// Save HTML to file
		$profileDir = Mage::getStoreConfig(self::XML_PATH_PROFILE_DIR,0) ?: Mage::getBaseDir('var') . DS . 'profile';
		if ( ! is_dir($profileDir)) {
			if ( ! @mkdir($profileDir, 0777)) {
				Mage::log("Aoe_Profiler could not mkdir: $profileDir");
				return FALSE;
			}
		}
		list($ms,$time) = explode(' ',microtime());
		list(,$ms) = explode('.',$ms);
		$fileName = $profileDir . DS . "$time-$ms.html";
		if ( ! @file_put_contents($fileName, $content)) {
			Mage::log("Aoe_Profiler could not write profiler file: $fileName");
			return FALSE;
		}
		return $fileName;
	}

}
