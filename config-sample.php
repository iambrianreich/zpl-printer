<?php

return [

    /**
     * The directory in which to store labels. The call below will set it to
     * your system temp folder. You can replace it with your own value.
     */
    'outputPath'    => sys_get_temp_dir(),

    /**
     * The template to use to generate filename. The %timestamp% string is
     * replaced by the current timestamp.
     */
    'fileTemplate'  => 'label-%timestamp%',

    /**
     * Timestamp format used to generate filenames.
     */
    'dateFormat'    => 'Y-m-d_H-i-s'
];
