<?php
/**
 * MY_Form_validation
 *
 * Extension to normal Validation Class. This will allow callback methods
 * to function propertly with HMVC extension.
 *
 * @uses CI
 * @uses _Form_validation
 * @package SilentMessage
 * @version 0.1
 * @copyright Copyright (c) 2012 All rights reserved.
 * @author Maciej 'Cichy' Świderski
 * @basedon https://bitbucket.org/wiredesignz/codeigniter-modular-extensions-hmvc/wiki/Home
 * @license GPL
 */
class MY_Form_validation extends CI_Form_validation
{
    public $CI;
}

/** application/libraries/MY_Form_validation **/
