<?php
defined("ABSPATH") or die("");
if (!defined('DUPLICATOR_PRO_VERSION'))
    exit; // Exit if accessed directly


class DUP_PRO_Multisite
{
    public $FilterSites = array();


    public function getDirsToFilter(){
        if(!empty($this->FilterSites)){
            $path_arr = array();
            $wp_content_dir = str_replace("\\", "/",WP_CONTENT_DIR);
            foreach ($this->FilterSites as $site_id){
                if($site_id == 1){
                    if(DUP_PRO_MU::getGeneration() == DUP_PRO_MU_Generations::ThreeFivePlus){
                        $uploads_dir = $wp_content_dir.'/uploads';
                        foreach(scandir($uploads_dir) as $node){
                            $fullpath = $uploads_dir . '/' .$node;
                            if ($node == '.' || $node == '.htaccess' || $node == '..') continue;
                            if(is_dir($fullpath)){
                                if($node != 'sites'){
                                    $path_arr[] = $fullpath;
                                }
                            }
                        }
                    }else{
                        $path_arr[] = $wp_content_dir.'/uploads';
                    }
                }else{
                    if(DUP_PRO_MU::getGeneration() == DUP_PRO_MU_Generations::ThreeFivePlus){
                        $path_arr[] = $wp_content_dir.'/uploads/sites/'.$site_id;
                    }else{
                        $path_arr[] = $wp_content_dir.'/blogs.dir/'.$site_id;
                    }
                }
            }
            return $path_arr;
        }else{
            return array();
        }
    }

    public function getTablesToFilter(){
        global $wpdb;

		$tables = array();

        if(!empty($this->FilterSites)) {
            foreach ($this->FilterSites as $site_id) {
                $prefix = $wpdb->get_blog_prefix($site_id);
                if ($site_id == 1) {
                    $default_tables = array(
                        'commentmeta',
                        'comments',
                        'links',
                        'options',
                        'postmeta',
                        'posts',
                        'terms',
                        'term_relationships',
                        'term_taxonomy',
                        'termmeta',
                    );
                    foreach ($default_tables as $tb) {
                        $tables[] = $prefix . $tb;
                    }
                } else {
                    $sql_query = $wpdb->prepare("SHOW TABLES LIKE '%s'", $prefix . '%');
                    $tables = $wpdb->get_col($sql_query);
                }
            }
        }
        return $tables;
    }

}