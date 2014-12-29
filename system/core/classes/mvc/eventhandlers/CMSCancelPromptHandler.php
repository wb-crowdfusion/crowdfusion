<?php

class CMSCancelPromptHandler {

    /* Bound to "cms-head" to include attach additional event handling to the form cancel button; will prompt user when form changes and cancel button clicked */
    public function generateAsset(Transport $output)
    {
        $output->String .= <<<EOD
            <script type="text/javascript" language="JavaScript">
                $(document).ready(function(){
                    $('input.button-cancel').click(function(event){
                        if(document.madeChanges) {
                            if(!confirm('Are you sure you want to CANCEL? If you leave this page, you will lose your changes.')) {
                                event.preventDefault();
                            }
                        }
                    });
                });
            </script>
EOD;
    }

}
