<?php
defined("ABSPATH") or die("");
/**
 * Defines the scope from which a filter item was created/retrieved from
 * @package DupicatorPro\classes
 */
class DUP_PRO_Archive_Filter_Scope_Base
{
    //All internal storage items that we decide to filter
    public $Core = array();
    //TODO: Enable with Settings UI
    //Global filter items added from settings
    public $Global = array();
    //Items when creating a package or template
    public $Instance = array();

}

/**
 * Defines the scope from which a filter item was created/retrieved from
 * @package DupicatorPro\classes
 */
class DUP_PRO_Archive_Filter_Scope_Directory extends DUP_PRO_Archive_Filter_Scope_Base
{
    // Items that are not readable
    public $Warning = array();
    // Items that are not readable
    public $Unreadable = array();
    // Directories containing other WordPress installs
    public $AddonSites = array();

}

/**
 * Defines the scope from which a filter item was created/retrieved from
 * @package DupicatorPro\classes
 */
class DUP_PRO_Archive_Filter_Scope_File extends DUP_PRO_Archive_Filter_Scope_Base
{
    // Items that are not readable
    public $Warning = array();
    // Items that are not readable
    public $Unreadable = array();
    //Items that are too large
    public $Size = array();

}

/**
 * Defines the filtered items that are pulled from there various scopes
 * @package DupicatorPro\classes
 */
class DUP_PRO_Archive_Filter_Info
{
    //Contains all folder filter info
    public $Dirs;
    //Contains all file filter info
    public $Files;
    //Contains all extensions filter info
    public $Exts;
	public $TreeSize;
	public $TreeWarning;

    public function __construct()
    {
        $this->Dirs  = new DUP_PRO_Archive_Filter_Scope_Directory();
        $this->Files = new DUP_PRO_Archive_Filter_Scope_File();
        $this->Exts  = new DUP_PRO_Archive_Filter_Scope_Base();
				$this->TreeSize = array();
		$this->TreeWarning = array();
    }
}

