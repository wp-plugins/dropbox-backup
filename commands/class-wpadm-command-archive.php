<?php


class WPadm_Command_Archive extends WPAdm_Сommand{
    public function execute(WPAdm_Command_Context $context)
    {
        require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-archive.php';
        $af = $this->getArchiveName($context->get('to_file'));
        $archive = new WPAdm_Archive($af, $context->get('to_file') . '.md5');
        $archive->setRemovePath($context->get('remove_path'));
        $files = $context->get('files');

        // если привышен максимальный размер архива, создадим новый
        if (file_exists($af) && filesize($af) > $context->get('max_file_size')) {
            //WPAdm_Core::log(filesize($af) . ', max=' . $context->get('max_file_size'));
            $af = $this->getNextArchiveName($context->get('to_file'));
            unset($archive);
            $archive = new WPAdm_Archive($af, $context->get('to_file') . '.md5');
            $archive->setRemovePath($context->get('remove_path'));
        }
        //WPAdm_Core::log('Add to archive ' . $af);
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
        //$f = array_pop($archives);
        return $f;
    }

    private function getNextArchiveName($name)
    {
        $arhives = glob("{$name}-*.zip");
        $n = 1 + count($arhives);
        $a = "{$name}-{$n}.zip";
        return $a;
    }
}