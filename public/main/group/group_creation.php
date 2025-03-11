<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_GROUP;

api_protect_course_script(true);

if (!api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

$currentUrl = api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq();
$allowGroupCategories = 'true' === api_get_setting('allow_group_categories');

$categories = [];
$numberOfGroups = GroupManager::get_number_of_groups() + 1;
if ($allowGroupCategories) {
    $groupCategories = GroupManager::get_categories();
    foreach ($groupCategories as $category) {
        $categories[$category['iid']] = $category['title'];
    }
}

/*	Create the groups */
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_groups':
            $groups = [];
            $useOnlyFirstCategory = false;
            $firstCategory = isset($_POST['group_0_category']) ? $_POST['group_0_category'] : 0;
            if (isset($_POST['same_category']) && $_POST['same_category']) {
                $useOnlyFirstCategory = true;
            }

            for ($i = 0; $i < $_POST['number_of_groups']; $i++) {
                $group1['name'] = empty($_POST['group_'.$i.'_name']) ? get_lang('Group').' '.$i : $_POST['group_'.$i.'_name'];
                $group1['category'] = isset($_POST['group_'.$i.'_category']) ? $_POST['group_'.$i.'_category'] : null;
                if ($useOnlyFirstCategory) {
                    $group1['category'] = $firstCategory;
                }
                $group1['tutor'] = isset($_POST['group_'.$i.'_tutor']) ? $_POST['group_'.$i.'_tutor'] : null;
                $group1['places'] = isset($_POST['group_'.$i.'_places']) ? $_POST['group_'.$i.'_places'] : null;
                $groups[] = $group1;
            }

            foreach ($groups as $index => $group) {
                if (!empty($_POST['same_tutor'])) {
                    $group['tutor'] = $_POST['group_0_tutor'];
                }
                if (!empty($_POST['same_places'])) {
                    $group['places'] = $_POST['group_0_places'];
                }

                GroupManager::create_group(
                    $group['name'],
                    $group['category'],
                    $group['tutor'],
                    $group['places']
                );
            }
            Display::addFlash(Display::return_message(get_lang('group(s) has (have) been added')));
            header('Location: '.$currentUrl);
            exit;

            break;
        case 'create_subgroups':
            GroupManager::create_subgroups(
                $_POST['base_group'],
                $_POST['number_of_groups']
            );
            Display::addFlash(Display::return_message(get_lang('group(s) has (have) been added')));
            header('Location: '.$currentUrl);
            exit;

            break;
        case 'create_class_groups':
            $classIds = [];
            foreach (array_keys($_POST) as $key) {
                if (strpos($key, 'checkbox_class_id_') !== false) {
                    $classId = str_replace('checkbox_class_id_', '', $key);
                    $classIds[] = (int)$classId;
                }
            }
            if (isset($_POST['is_consistent_link'])) {
                GroupManager::create_usergroup_consistent_groups($_POST['group_category'], $classIds);
            } else {
                GroupManager::create_class_groups($_POST['group_category'], $classIds);
            }
            Display::addFlash(Display::return_message(get_lang('group(s) has (have) been added')));
            header('Location: '.$currentUrl);
            exit;

            break;
    }
}

$nameTools = get_lang('New groups creation');
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
    'name' => get_lang('Groups'),
];
Display::display_header($nameTools, 'Group');

if (isset($_POST['number_of_groups'])) {
    if (!is_numeric($_POST['number_of_groups']) || (int) ($_POST['number_of_groups']) < 1) {
        echo Display::return_message(
            get_lang('Please enter the desired number of groups').'<br /><br />
            <a href="group_creation.php?'.api_get_cidreq().'">&laquo; '.get_lang('Back').'</a>',
            'error',
            false
        );
    } else {
        $number_of_groups = (int) ($_POST['number_of_groups']);
        if ($number_of_groups > 1) {
            ?>
    <script>
    var number_of_groups = <?php echo $number_of_groups; ?>;
    function switch_state(key) {
        ref = document.getElementById(key+'_0');
        for(i=1; i<number_of_groups; i++) {
            var id = "#"+key+'_'+i;
            element = document.getElementById(key+'_'+i);
            element.disabled = !element.disabled;
            disabled = element.disabled;
            $(id).prop('disabled', disabled);
            $(id).prop('value', ref.value);
            //$(id).selectpicker('refresh');
        }
        if (disabled) {
            ref.addEventListener("change", copy, false);
        } else {
            ref.removeEventListener("change", copy, false);
        }
        copy_value(key);
    }

    function copy(e) {
        key = e.currentTarget.id;
        var re = new RegExp ('_0', '') ;
        var key = key.replace(re, '') ;
        copy_value(key);
    }

    function copy_value(key) {
        ref = document.getElementById(key+'_0');
        for( i=1; i<number_of_groups; i++) {
            element = document.getElementById(key+'_'+i);
            element.value = ref.value;
        }
    }
    </script>
    <?php
        }
        $form = new FormValidator('create_groups_step2', 'POST', api_get_self().'?'.api_get_cidreq());

        // Modify the default templates
        $renderer = $form->defaultRenderer();
        $form_template = "<form {attributes}>\n<div class='create-groups'>\n<table>\n{content}\n</table>\n</div>\n</form>";
        $renderer->setFormTemplate($form_template);
        $element_template = <<<EOT
        <tr class="separate">
		<td>
			<!-- BEGIN required -->
			<span class="form_required">*</span> <!-- END required -->{label}
		</td>
		<td>
			<!-- BEGIN error -->
			<span class="form_error">{error}</span><br /><!-- END error -->	{element}
		</td>
	</tr>

EOT;
        $renderer->setCustomElementTemplate($element_template);
        $form->addElement('header', $nameTools);
        $form->addElement('hidden', 'action');
        $form->addElement('hidden', 'number_of_groups');
        $defaults = [];
        // Table heading
        $group_el = [];
        $group_el[] = $form->createElement('static', null, null, '<b>'.get_lang('Group name').'</b>');

        if ($allowGroupCategories) {
            $group_el[] = $form->createElement('static', null, null, '<b>'.get_lang('Group category').'</b>');
        }
        $group_el[] = $form->createElement('static', null, null, '<b>'.get_lang('seats (optional)').'</b>');
        $form->addGroup($group_el, 'groups', null, '</td><td>', false);
        // Checkboxes
        if ($_POST['number_of_groups'] > 1) {
            $group_el = [];
            $group_el[] = $form->createElement('static', null, null, ' ');
            if ($allowGroupCategories) {
                if (!isset($_GET['category_id'])) {
                    $group_el[] = $form->createElement(
                        'checkbox',
                        'same_category',
                        null,
                        get_lang('same for all'),
                        ['onclick' => "javascript: switch_state('category');"]
                    );
                } else {
                    $group_el[] = $form->createElement('static', null, null, ' ');
                }
            }
            $group_el[] = $form->createElement(
                'checkbox',
                'same_places',
                null,
                get_lang('same for all'),
                ['onclick' => "javascript: switch_state('places');"]
            );
            $form->addGroup($group_el, 'groups', null, '</td><td>', false);
        }
        // Properties for all groups
        for ($group_number = 0; $group_number < $_POST['number_of_groups']; $group_number++) {
            $group_el = [];
            $group_el[] = $form->createElement('text', 'group_'.$group_number.'_name');
            if ($allowGroupCategories) {
                if(isset($_GET['category_id'])) {
                    $group_el[] = $form->createElement(
                        'select',
                        'group_' . $group_number . '_category',
                        null,
                        $categories,
                        [
                            'id' => 'category_' . $group_number,
                            'disabled' => 'true',
                            'style' => 'background-color: #f0f0f0;'
                        ]
                    );
                    $group_el[] = $form->createElement('hidden', 'group_'.$group_number.'_category', $_GET['category_id']);
                    $defaults['group_'.$group_number.'_category'] = $_GET['category_id'];
                } else {
                    $group_el[] = $form->createElement(
                        'select',
                        'group_' . $group_number . '_category',
                        null,
                        $categories,
                        ['id' => 'category_' . $group_number]
                    );
                }
            } else {
                $group_el[] = $form->createElement('hidden', 'group_'.$group_number.'_category', 0);

                $defaults['group_'.$group_number.'_category'] = null;
            }
            $group_el[] = $form->createElement(
                'text',
                'group_'.$group_number.'_places',
                null,
                ['class' => 'span1', 'id' => 'places_'.$group_number]
            );

            if ($_POST['number_of_groups'] < 10000) {
                if ($numberOfGroups < 10) {
                    $prev = '000';
                } elseif ($numberOfGroups < 100) {
                    $prev = '00';
                } elseif ($numberOfGroups < 1000) {
                    $prev = '0';
                } else {
                    $prev = '';
                }
            }

            $defaults['group_'.$group_number.'_name'] = get_lang('Group').' '.$prev.$numberOfGroups++;
            $form->addGroup($group_el, 'group_'.$group_number, null, '</td><td>', false);
        }
        $defaults['action'] = 'create_groups';
        $defaults['number_of_groups'] = (int) $_POST['number_of_groups'];
        $form->setDefaults($defaults);
        $form->addButtonCreate(get_lang('Create group(s)'), 'submit');
        $form->display();
    }
} else {
    /*
     * Show form to generate new groups
     */
    $create_groups_form = new FormValidator('create_groups', 'post', api_get_self().'?'.api_get_cidreq().(isset($_GET['category_id']) ? '&category_id='.$_GET['category_id'] : ''));
    $create_groups_form->addElement('header', $nameTools);
    $create_groups_form->addText('number_of_groups', get_lang('Number of groups to create'), null, ['value' => '1']);
    $create_groups_form->addButton('submit', get_lang('Proceed to create group(s)'), 'plus', 'primary');
    $defaults = [];
    $defaults['number_of_groups'] = 1;
    $create_groups_form->setDefaults($defaults);
    $create_groups_form->display();

    /*
     * Show form to generate subgroups
     */
    if ($allowGroupCategories) {
        $groups = GroupManager::get_group_list();
        if (!empty($groups)) {
            $base_group_options = [];
            foreach ($groups as $index => $group) {
                $groupId = $group['iid'];
                $number_of_students = GroupManager::number_of_students($groupId);
                if ($number_of_students > 0) {
                    $base_group_options[$groupId] = $group['name'].' ('.$number_of_students.' '.get_lang('Users').')';
                }
            }
            /*if (count($base_group_options) > 0) {
                $create_subgroups_form = new FormValidator(
                    'create_subgroups',
                    'post',
                    api_get_self().'?'.api_get_cidreq()
                );
                $create_subgroups_form->addElement('header', get_lang('Create subgroups'));
                $create_subgroups_form->addElement('html', get_lang('Create subgroupsInfo'));
                $create_subgroups_form->addElement('hidden', 'action');
                $group_el = [];
                $group_el[] = $create_subgroups_form->createElement(
                    'static',
                    null,
                    null,
                    get_lang('Create number of groups')
                );
                $group_el[] = $create_subgroups_form->createElement('text', 'number_of_groups', null, ['size' => 3]);
                $group_el[] = $create_subgroups_form->createElement('static', null, null, get_lang('groups with members from'));
                $group_el[] = $create_subgroups_form->createElement('select', 'base_group', null, $base_group_options);
                $group_el[] = $create_subgroups_form->addButtonSave(get_lang('Validate'), 'submit', true);
                $create_subgroups_form->addGroup($group_el, 'create_groups', null, null, false);
                $defaults = [];
                $defaults['action'] = 'create_subgroups';
                $create_subgroups_form->setDefaults($defaults);
                $create_subgroups_form->display();
            }*/
        }
    }

    /*
     * Show form to generate groups from classes subscribed to the course
     */
    $options['where'] = [' usergroup.course_id = ? ' => api_get_course_int_id()];
    $obj = new UserGroupModel();
    $classes = $obj->getUserGroupInCourse($options);
    if (count($classes) > 0) {
        $description = '<p>'.get_lang('Using this option, you can create groups based on the classes subscribed to your course.').'</p><br>';

        $classForm = new FormValidator(
            'create_class_groups_form',
            'post',
            api_get_self().'?'.api_get_cidreq()
        );
        $classForm->addHeader(get_lang('Groups from classes'));

        $classForm->addHtml($description);

        $classGroup = [];

        foreach ($classes as $index => $class) {
            $number_of_users = count($obj->get_users_by_usergroup($class['id']));
            // $classForm->addCheckBox('checkbox_class_id_'.$class['id'], $class['title'] . ' ('.$number_of_users.' '.get_lang('Users').')');
            $classGroup[] = $classForm->createElement('checkbox', 'checkbox_class_id_'.$class['id'], null, $class['title'] . ' ('.$number_of_users.' '.get_lang('Users').')');
        }

        $classForm->addGroup(
            $classGroup,
            '',
            null,
            null,
            false
        );

        $classForm->addElement('hidden', 'action');
        if ($allowGroupCategories) {
            if (isset($_GET['category_id'])) {
                $classForm->addElement('hidden', 'group_category', $_GET['category_id']);
            } else {
                $classForm->addSelect('group_category', null, $categories);
            }
        } else {
            $classForm->addElement('hidden', 'group_category');
        }

        $classForm->addHtml('<div style="height: 1px; width: 100%; background-color: #e6e6e6; border-radius: 8px;" /><br>');

        $classForm->addCheckBox('is_consistent_link', null, 'Lier les classes aux groupes crées ?',
            ['title' => 'Si elle est désactivé les classes lié à ce groupe sont simplement une copie des utilisateurs de la classe séléctionnée. A contrario si elle est activé, la classe séléctionnée est lié au groupe et les modifications de la classe sont répercuté sur le groupe (ce mode empêche la modification du groupe)']
        );
        $classForm->addHtml('<p class="alert alert-info">Si elle est désactivé les classes lié à ce groupe sont simplement une copie des utilisateurs de la classe séléctionnée. A contrario si elle est activé, la classe séléctionnée est lié au groupe et les modifications de la classe sont répercuté sur le groupe (ce mode empêche la modification du groupe)</p>');

        $classForm->addButtonSave(get_lang('Validate'));
        $defaults['action'] = 'create_class_groups';
        $classForm->setDefaults($defaults);
        $classForm->display();
    }
}

Display::display_footer();
