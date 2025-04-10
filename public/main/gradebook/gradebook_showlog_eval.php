<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
api_block_anonymous_users();
GradebookUtils::block_students();

$selectCat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;

$interbreadcrumb[] = [
    'url' => Category::getUrl(),
    'name' => get_lang('Assessments'),
];
$interbreadcrumb[] = [
    'url' => Category::getUrl().'selectcat='.$selectCat,
    'name' => get_lang('Details'),
];
$interbreadcrumb[] = [
    'url' => 'gradebook_showlog_eval.php?visiblelog='.Security::remove_XSS($_GET['visiblelog']).'&amp;selectcat='.$selectCat,
    'name' => get_lang('Assessment history'),
];
$this_section = SECTION_COURSES;
Display::display_header('');
echo Display::page_header(get_lang('Assessment history'));

$t_linkeval_log = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINKEVAL_LOG);
$t_user = Database::get_main_table(TABLE_MAIN_USER);
$visible_log = Security::remove_XSS($_GET['visiblelog']);

$evaledit = Evaluation :: load($visible_log);
$sql = "SELECT le.title,le.description,le.weight,le.visible,le.type,le.created_at, us.id as user_id
        FROM $t_linkeval_log le
        INNER JOIN $t_user us
        ON le.user_id_log = us.id
        WHERE
            id_linkeval_log=".$evaledit[0]->get_id()." AND
            type = 'evaluation'
        ";
$result = Database::query($sql);
$list_info = [];
while ($row = Database::fetch_row($result)) {
    $list_info[] = $row;
}

foreach ($list_info as $key => $info_log) {
    $list_info[$key][5] = $info_log[5] ? api_convert_and_format_date($info_log[5]) : 'N/A';
    $list_info[$key][3] = 1 == $info_log[3] ? get_lang('Assessments visible') : get_lang('Assessments invisible');
    $userInfo = api_get_user_info($info_log[6]);
    if ($userInfo) {
        $list_info[$key][6] = $userInfo['complete_name_with_message_link'];
    } else {
        $list_info[$key][6] = '';
    }
}

$parameters = [
    'visiblelog' => $visible_log,
    'selectcat' => intval($_GET['selectcat']),
];
$table = new SortableTableFromArrayConfig($list_info, 1, 20, 'gradebookeval');
$table->set_additional_parameters($parameters);

$table->set_header(0, get_lang('Assessment name'));
$table->set_header(1, get_lang('Assessment description'));
$table->set_header(2, get_lang('Previous weight of resource'));
$table->set_header(3, get_lang('Assessment visibility'));
$table->set_header(4, get_lang('Category'));
$table->set_header(5, get_lang('Date'));
$table->set_header(6, get_lang('Who changed it'));

$table->display();
Display :: display_footer();
