<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ChatVideo;

require_once __DIR__.'/../../../global.inc.php';

if (api_is_anonymous()) {
    api_not_allowed(true);
}

$roomId = isset($_GET['room']) ? $_GET['room'] : null;

$entityManager = Database::getManager();

$chatVideo = $entityManager->find(ChatVideo::class, $roomId);

if (!$chatVideo) {
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$friend_html = SocialManager::listMyFriendsBlock($user_id, '', false);
$isSender = $chatVideo->getFromUser() === api_get_user_id();
$isReceiver = $chatVideo->getToUser() === api_get_user_id();

if (!$isSender && !$isReceiver) {
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

if ($isSender) {
    $chatUser = api_get_user_info($chatVideo->getToUser());
} elseif ($isReceiver) {
    $chatUser = api_get_user_info($chatVideo->getFromUser());
}
$idUserLocal = api_get_user_id();
$userLocal = api_get_user_info($idUserLocal, true);
$htmlHeadXtra[] = '<script type="text/javascript" src="'
    . api_get_path(WEB_PUBLIC_PATH).'assets/simpleWebRTC/latest-v2.js'
    . '"></script>' . "\n";

$navigator = api_get_navigator();

Display::addFlash(
    Display::return_message(get_lang('This feature has been disabled because the libraries it depends on are no longer unmaintained.'), 'error')
);

$template = new Template();
$template->assign('room_name', $chatVideo->getTitle());
$template->assign('chat_user', $chatUser);
$template->assign('user_local', $userLocal);
$template->assign('block_friends', $friend_html);
$template->assign('navigator_is_firefox', $navigator['name'] == 'Mozilla');

$tpl = $template->get_template('chat/video.tpl');
$content = $template->fetch($tpl);

$templateHeader = Display::getMdiIcon('video')
    . $chatVideo->getTitle();

$template->assign('header', $templateHeader);
$template->assign('content', $content);
$template->assign(
    'message',
    Display::return_message(get_lang('Your browser does not support native video transmission.'), 'warning')
);
$template->display_one_col_template();
