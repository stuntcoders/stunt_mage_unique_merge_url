<?php

class Stuntcoders_UniqueMergedUrl_Model_Design_Package extends Mage_Core_Model_Design_Package
{
    /**
     * @return string
     */
    private function _getMergeCssJsSuffix()
    {
        $flagModel = Mage::getModel('core/flag', array('flag_code' => 'merged_js_css_suffix'))->loadSelf();

        return $flagModel->getFlagData();
    }

    private function _generateMergeCssJsSuffix()
    {
        $flagModel = Mage::getModel('core/flag', array('flag_code' => 'merged_js_css_suffix'))->loadSelf();
        $flagModel->setFlagData('-' . Mage::helper('core')->getRandomString('8'));
        $flagModel->save();
    }

    /**
     * @param $files
     * @return string
     */
    public function getMergedJsUrl($files)
    {
        $suffix = $this->_getMergeCssJsSuffix();
        $targetFilename = md5(implode(',', $files)) . $suffix . '.js';
        $targetDir = $this->_initMergerDir('js');

        if (!$targetDir) {
            return '';
        }

        if ($this->_mergeFiles($files, $targetDir . DS . $targetFilename, false, null, 'js')) {
            return Mage::getBaseUrl('media', Mage::app()->getRequest()->isSecure()) . 'js/' . $targetFilename;
        }

        return '';
    }

    /**
     * @param $files
     * @return string
     */
    public function getMergedCssUrl($files)
    {
        $isSecure = Mage::app()->getRequest()->isSecure();
        $mergerDir = $isSecure ? 'css_secure' : 'css';
        $targetDir = $this->_initMergerDir($mergerDir);

        if (!$targetDir) {
            return '';
        }

        $baseMediaUrl = Mage::getBaseUrl('media', $isSecure);
        $hostname = parse_url($baseMediaUrl, PHP_URL_HOST);
        $port = parse_url($baseMediaUrl, PHP_URL_PORT);

        if (false === $port) {
            $port = $isSecure ? 443 : 80;
        }

        $suffix = $this->_getMergeCssJsSuffix();
        $targetFilename = md5(implode(',', $files) . "|{$hostname}|{$port}") . $suffix . '.css';

        $mergeFilesResult = $this->_mergeFiles(
            $files, $targetDir . DS . $targetFilename,
            false,
            array($this, 'beforeMergeCss'),
            'css'
        );

        if ($mergeFilesResult) {
            return $baseMediaUrl . $mergerDir . '/' . $targetFilename;
        }

        return '';
    }

    /**
     * @return  bool
     */
    public function cleanMergedJsCss()
    {
        $result = (bool)$this->_initMergerDir('js', true);
        $result = (bool)$this->_initMergerDir('css', true) && $result;

        $this->_generateMergeCssJsSuffix();

        return (bool)$this->_initMergerDir('css_secure', true) && $result;
    }
}
