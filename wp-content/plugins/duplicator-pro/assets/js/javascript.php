<?php defined("ABSPATH") or die(""); ?>
<script>
/* ============================================================================
 * DESCRIPTION: Methods and Objects in this file are global and common in 
 * nature use this file to place all shared methods and varibles */

//UNIQUE NAMESPACE
DupPro = new Object();
DupPro.UI = new Object();
DupPro.Pack = new Object();
DupPro.Tools = new Object();
DupPro.Settings = new Object();
DupPro.Storage = new Object();
DupPro.Storage.Dropbox = new Object();
DupPro.Storage.OneDrive = new Object();
DupPro.Storage.FTP = new Object();
DupPro.Storage.SFTP = new Object();
DupPro.Storage.GDrive = new Object();
DupPro.Storage.S3 = new Object();
DupPro.Schedule = new Object();
DupPro.Template = new Object();
DupPro.Support = new Object();
DupPro.Debug = new Object();

//GLOBAL CONSTANTS
DupPro.DEBUG_AJAX_RESPONSE = false;
DupPro.AJAX_TIMER = null;

DupPro._WordPressInitDateTime	= '<?php echo  current_time("D M d Y H:i:s O")?>';
DupPro._WordPressInitTime		= '<?php echo  current_time("H:i:s")?>';
DupPro._ServerInitDateTime		= '<?php echo  date("D M d Y H:i:s O")?>';
DupPro._ClientInitDateTime		= new Date();

/* ============================================================================
 *  BASE NAMESPACE: All methods at the top of the Duplicator Namespace 
 * ============================================================================ */

/* Starts a timer for Ajax calls */
DupPro.StartAjaxTimer = function () 
{
	DupPro.AJAX_TIMER = new Date();
};

/*	Ends a timer for Ajax calls */
DupPro.EndAjaxTimer = function () 
{
	var endTime = new Date();
	DupPro.AJAX_TIMER = (endTime.getTime() - DupPro.AJAX_TIMER) / 1000;
};

/*	Reloads the current window
 *	@param data		An xhr object  */
DupPro.ReloadWindow = function (data, queryString)
{
	if (DupPro.DEBUG_AJAX_RESPONSE) {
		DupPro.Pack.ShowError('debug on', data);
	} else {
        var url = window.location.href;
        if(queryString !== 'undefined') {
            var character = '?';
            if(url.indexOf('?') > -1) {
                character = '&';
            }
            url += character + queryString;
        }
        window.location = url;
	}
};

/* Basic Util Methods here */
DupPro.OpenLogWindow = function (log) 
{
	var logFile = log || null;
	if (logFile == null) {
		window.open('?page=duplicator-pro-tools', 'Log Window');
	} else {
		window.open('<?php echo DUPLICATOR_PRO_SSDIR_URL; ?>' + '/' + log)
	}
};

DupPro.humanFileSize = function (size)
{
	var i = Math.floor( Math.log(size) / Math.log(1024) );
    return ( size / Math.pow(1024, i) ).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
};


/* ============================================================================
 *  UI NAMESPACE: All methods at the top of the Duplicator Namespace		  
 *  =========================================================================== */

/*  Stores the state of a view into the database  */
DupPro.UI.SaveViewStateByPost = function (key, value) 
{
	if (key != undefined && value != undefined) {
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			data: {action: 'DUP_PRO_UI_ViewState_SaveByPost', key: key, value: value},
			success: function (data) {},
			error: function (data) {}
		});
	}
}

/*  Animate the progress bar  */
DupPro.UI.AnimateProgressBar = function (id) 
{
	//Create Progress Bar
	var $mainbar = jQuery("#" + id);
	$mainbar.progressbar({value: 100});
	$mainbar.height(25);
	$mainbar.width(20);
	runAnimation($mainbar);
	
	function runAnimation($pb) {
		$pb.css({"padding-left": "0%", "padding-right": "90%"});
		$pb.progressbar("option", "value", 100);
		$pb.animate({paddingLeft: "90%", paddingRight: "0%"}, 2500, "linear", function () {
			runAnimation($pb);
		});
	}
}

/*	Toggle MetaBoxes */
DupPro.UI.ToggleMetaBox = function () 
{
	var $title = jQuery(this);
	var $panel = $title.parent().find('.dup-box-panel');
	var $arrow = $title.parent().find('.dup-box-arrow i');
	var key = $panel.attr('id');
	var value = $panel.is(":visible") ? 0 : 1;
	$panel.toggle();
	DupPro.UI.SaveViewStateByPost(key, value);
	(value)
		? $arrow.removeClass().addClass('fa fa-caret-up')
		: $arrow.removeClass().addClass('fa fa-caret-down');

}

DupPro.UI.ClearTraceLog = function ()
{
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        dataType: "json",
        data: {action: 'duplicator_pro_delete_trace_log'},
        success: function (data) {},
        error: function (data) {}
    });
}

/*	Toggle Password input */
DupPro.UI.TogglePasswordDisplay = function (display, inputID) 
{
	if (display) {
		document.getElementById(inputID).type = "text";
	} else {
		document.getElementById(inputID).type = "password";
	}
}

/* Clock generator, used to show an active clock.
 * Intended use is to be called once per page load
 * such as: 	
 *		<div id="dpro-clock-container"></div>
 *		DupPro.UI.Clock(DupPro._WordPressInitTime); */
DupPro.UI.Clock  = function() 
{
	var timeDiff;
	var timeout;

	function addZ(n) {
	  return (n < 10 ? '0' : '') + n;
	}

	function formatTime(d) {
	  return addZ(d.getHours()) + ':' +	 addZ(d.getMinutes()) + ':' +  addZ(d.getSeconds());
	}

	return function (s) {

	  var now = new Date();
	  var then;
	  // Set lag to just after next full second
	  var lag = 1015 - now.getMilliseconds();

	  // Get the time difference when first run
	  if (s) {
		s = s.split(':');
		then = new Date(now);
		then.setHours(+s[0], +s[1], +s[2], 0);
		timeDiff = now - then;
	  }

	  now = new Date(now - timeDiff);
	  jQuery('#dpro-clock-container').html(formatTime(now));
	  timeout = setTimeout(DupPro.UI.Clock, lag);
	};
}();



jQuery(document).ready(function ($) 
{
	//INIT: DupPro Tabs
	$("div[data-dpro-tabs='true']").each(function () 
	{
		//Load Tab Setup
		var $root   = $(this);
		var $lblRoot = $root.find('ul:first-child')
		var $lblKids = $lblRoot.children('li');
		var $lblKidsA = $lblRoot.children('li a');
		var $pnls	 = $root.children('div');

		//Apply Styles
		$root.addClass('categorydiv');
		$lblRoot.addClass('category-tabs');
		$pnls.addClass('tabs-panel').css('display', 'none');
		$lblKids.eq(0).addClass('tabs').css('font-weight', 'bold');
		$pnls.eq(0).show();
		
		var _clickEvt = function(evt) 
		{
			var $target = $(evt.target);
			if (evt.target.nodeName == 'A') {
				var $target =  $(evt.target).parent();
			}
			var $lbls = $target.parent().children('li');
			var $pnls = $target.parent().parent().children('div');
			var index = $target.index();
			
			$lbls.removeClass('tabs').css('font-weight', 'normal');
			$lbls.eq(index).addClass('tabs').css('font-weight', 'bold');
			$pnls.hide();
			$pnls.eq(index).show();
		}

		//Attach Events
		$lblKids.click(_clickEvt);
		$lblKids.click(_clickEvt);
	 });

	//INIT: Toggle MetaBoxes
	$('div.dup-box div.dup-box-title').each(function () {
		var $title = $(this);
		var $panel = $title.parent().find('.dup-box-panel');
		var $arrow = $title.find('.dup-box-arrow');
		$title.click(DupPro.UI.ToggleMetaBox);
		($panel.is(":visible"))
				? $arrow.html('<i class="fa fa-caret-up"></i>')
				: $arrow.html('<i class="fa fa-caret-down"></i>');
	});

	DupPro.UI.loadQtip = function()
	{
		//Look for tooltip data
		$('i[data-tooltip!=""]').qtip({
			content: {
				attr: 'data-tooltip',
				title: {
					text: function() { return  $(this).attr('data-tooltip-title'); }
				}
			},
			style: {
				classes: 'qtip-light qtip-rounded qtip-shadow',
				width: 500
			},
			 position: {
				my: 'top left',
				at: 'bottom center'
			}
		});
	}

	DupPro.UI.loadQtip();
	

	//HANDLEBARS HELPERS
	if  (typeof(Handlebars) != "undefined"){

		function _handleBarscheckCondition(v1, operator, v2) {
			switch(operator) {
				case '==':
					return (v1 == v2);
				case '===':
					return (v1 === v2);
				case '!==':
					return (v1 !== v2);
				case '<':
					return (v1 < v2);
				case '<=':
					return (v1 <= v2);
				case '>':
					return (v1 > v2);
				case '>=':
					return (v1 >= v2);
				case '&&':
					return (v1 && v2);
				case '||':
					return (v1 || v2);
				case 'obj||':
					v1 = typeof(v1) == 'object' ? v1.length : v1;
					v2 = typeof(v2) == 'object' ? v2.length : v2;
					return (v1 !=0 || v2 != 0);
				default:
					return false;
			}
		}

		Handlebars.registerHelper('ifCond', function (v1, operator, v2, options) {
			return _handleBarscheckCondition(v1, operator, v2)
						? options.fn(this)
						: options.inverse(this);
		});

		Handlebars.registerHelper('if_eq',		function(a, b, opts) { return (a == b) ? opts.fn(this) : opts.inverse(this);});
		Handlebars.registerHelper('if_neq',		function(a, b, opts) { return (a != b) ? opts.fn(this) : opts.inverse(this);});
	}

	//Prevent notice boxes from flashing as its re-positioned in DOM
	$('div.dpro-wpnotice-box').show(300);

});
</script>
