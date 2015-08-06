<?php


class WPadm_Command_Archive extends WPAdm_Сommand{
    public function execute(WPAdm_Command_Context $context)
    {
        require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-archive.php';
        $af = $this->getArchiveName($context->get('to_file'));
        $archive = new WPAdm_Archive($af, $context->get('to_file') . '.md5');
        $archive->setRemovePath($context->get('remove_path'));
        $files = $context->get('files');
        if (file_exists($af) && filesize($af) > $context->get('max_file_size')) {
            $af = $this->getNextArchiveName($context->get('to_file'));
            unset($archive);
            $archive = new WPAdm_Archive($af, $context->get('to_file') . '.md5');
            $archive->setRemovePath($context->get('remove_path'));
        }
        $archive->add(implode(',', $files));
        return true;
    }

    private function getArchiveName($name)
    {
        $archives = glob("{$name}-*.zip");
        if (empty($archives)) {
            return "{$name}-1.zip";
        }
        $n = count($archives);
        $f = "{$name}-{$n}.zip";
        return $f;
    }

    private function getNextArchiveName($name)
    {
        $archives = glob("{$name}-*.zip");
        $n = 1 + count($archives);
        $a = "{$name}-{$n}.zip";
        return $a;
    }
}