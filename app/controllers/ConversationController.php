<?php

namespace app\controllers;

use ___PHPSTORM_HELPERS\object;
use yii;
use yii\web\Controller;
use app\models\Conversation;
use app\models\Message;
use app\models\User;

class ConversationController extends PjaxController
{
    const EVENT_SEND_MESSAGE = "sendMessage";

    private $messageText;

    public function beforeAction($action) {

        // Check user on access
        if (Yii::$app->getUser()->getIsGuest()) {
            Yii::$app->getResponse()->redirect('/');
            return false;
        }

        /* Add event handler
         *
         * Use example:
         * $this->messageText = "Some message text";
         * $this->trigger(self::EVENT_SEND_MESSAGE);
         */
        $this->on(self::EVENT_SEND_MESSAGE, array($this, 'sendMessageHandler'));
        return parent::beforeAction($action);
    }

    protected function sendMessageHandler($event) {
        $email = Yii::$app->getUser()->getIdentity()->email;
        $mail  = Yii::$app->getComponent('mail');

        $mail->setTo($email);
        $mail->setSubject('Private message');
        $mail->setBody($this->messageText);
        $mail->send();
    }

    // Check user rights to access conversation
    private function checkAccess($id) {
        if(!isset($id)) {
            return true;
        }

        $conversation = Conversation::find($id);

        if (empty($conversation) ||
            !($conversation->isConversationMember(Yii::$app->getUser()->getIdentity()->id))) {
            return false;
        }

        return true;
    }

    public function actionConversationCreate() {
        if (!Yii::$app->getRequest()->getIsAjax()) {
            return Yii::$app->getResponse()->redirect('conversation-list');
        }

        $this->layout = 'block';
        $conversation = new Conversation();

        if(Yii::$app->getRequest()->getIsPost()) {
            if(isset($_POST['members']) && count($_POST['members'] > 0)) {

                if(isset($_POST['message']) && $_POST['message'] != null) {

                    $owner =  Yii::$app->getUser()->getIdentity();

                    $conversation->creator = $owner->id;
                    $conversation->save();
                    $conversation->refresh();
                    $conversation->link('users', $owner);

                    // Add message to conversation
                    $message = new Message();
                    $message->user_id = $owner->id;
                    $message->conversation_id = $conversation->id;
                    $message->body = $_POST['message'];
                    $message->save();

                    foreach($_POST['members'] as $key => $value) {
                    $user         = User::find($key);
                    $conversation = $conversation->addSubscribed($user);
                    }

                    $conversation->title = isset($_POST['Conversation']['title']) ? $_POST['Conversation']['title'] : null;
                    $conversation->save();

                    return json_encode(array('redirect' => '/conversation/' . $conversation->id));
                } else {
                    $result = array(
                        'status' => 'error',
                        'errors' => array('message' => 'Conversation must have first message')
                    );
                    return json_encode($result);
                }
            } else {
                $result = array(
                    'status' => 'error',
                    'errors' => array('new-member-list' => 'Conversation must have 1 or more members')
                );
                return json_encode($result);
            }
        } else {
            $param = array(
                'model' => $conversation
            );

            return $this->render('conversationCreate', $param);
        }
    }

    public function actionConversationList() {
        // Get all users conversations
        $conversations = Yii::$app->getUser()->getIdentity()->conversations;
        $viewParams = array();

        foreach($conversations as $conversation) {
            $row = array();

            $row['id']      = $conversation->id;
            $row['title']   = $conversation->title;
            $row['private'] = $conversation->isPrivate();
            $row['users']   = array();

            foreach ($conversation->users as $user) {
                if (Yii::$app->getUser()->getIdentity()->id != $user->id) {
                    $row['users'][] = $user;
                }
            }

            $row['unread'] = $conversation->isUnread(Yii::$app->getUser()->getIdentity()->id);
            $message = Message::getLastInConversation($conversation->id);

            if ($message != null) {
                $row['lastMessage']       = $message;
                $lastMessageUser          = $message->user;
                $row['lastMessageUser']   = $lastMessageUser->userName;
                $row['lastMessageAvatar'] = $lastMessageUser->avatar;
            }

            $viewParams[] = $row;
        }
        return $this->render('conversationList', array(
            'conversations' => $viewParams,
        ));
    }

    /*
     * get conversation
     */
    public function actionIndex($id = null) {

        if (!is_numeric($id) || !$this->checkAccess($id)) {
            return Yii::$app->getResponse()->redirect('conversation/conversation-list');
        }

        $conversation = Conversation::find($id);
        $creator      = $conversation->getCreator();
        $user         = Yii::$app->getUser()->getIdentity();

        // Mark conversation as read
        $conversation->markAsRead($user->id);
        return $this->render('conversation', array(
            'conversationCreator' => $creator,
            'conversationId'      => $conversation->id,
            'conversationMembers' => $conversation->users,
            'conversationTitle'   => $conversation->title,
            'is_creator'          => $user->id == $creator->id,
            'messages'            => $conversation->messages,
            'user'                => $user
        ));
    }

    public function actionMemberNotSubscribeList() {

        if (!Yii::$app->getRequest()->getIsAjax()) {
            return Yii::$app->getResponse()->redirect('conversation-list');
        }

        if (isset($_POST['id_conversation']) && !$this->checkAccess($_POST['id_conversation'])) {
            return Yii::$app->getResponse()->redirect('conversation-list');
        }

        $conversation = isset($_POST['id_conversation']) ? Conversation::find($_POST['id_conversation']) : new Conversation();
        $users        = array();

        foreach ($conversation->unsubscribedUsers as $user) {

            if($user->id == Yii::$app->getUser()->getIdentity()->id) {
                continue;
            }

            $users[] = array(
                'id'   => $user->id,
                'name' => $user->first_name.' '.$user->last_name
            );
        }

        return json_encode($users);
    }

    public function actionMemberRemove() {
        $result = array(
            'redirect' => Yii::$app->getUrlManager()->createAbsoluteUrl('/conversation/conversation-list'),
            'status'   => 'ok'
        );
        $user = Yii::$app->getUser()->getIdentity();

        if (!Yii::$app->getRequest()->getIsAjax() ||
            !isset($_POST['id_user']) ||
            !isset($_POST['id_conversation']) ||
            !$this->checkAccess($_POST['id_conversation']) && $_POST['id_user'] != $user->id) {
            $result['status'] = 'redirect';
        }

        $conversation = Conversation::find($_POST['id_conversation']);

        if($_POST['id_user'] != $user->id && $conversation->getCreator()->id != $user->id) {
            $result['status'] = 'redirect';
        }

        if('ok' == $result['status']) {
            $conversation = Conversation::find($_POST['id_conversation']);
            $conversation->deleteMember($_POST['id_user']);
        }

        if ($_POST['id_user'] == $user->id) {
            $result['status'] = 'redirect';
        }

        return json_encode($result);
    }

    public function actionMemberSave() {

        if (!Yii::$app->getRequest()->getIsAjax() ||
            !isset($_POST['id_user']) ||
            !isset($_POST['id_conversation']) ||
            !$this->checkAccess($_POST['id_conversation'])) {
            echo 'error';
            return Yii::$app->getResponse()->redirect('conversation-list');
        }

        $conversation = Conversation::find($_POST['id_conversation']);
        $conversation = $conversation->addSubscribed(User::find($_POST['id_user']));
        $conversation->save();

        if ($conversation->id != $_POST['id_conversation']) {
            echo Yii::$app->getUrlManager()->createAbsoluteUrl('/conversation/'.$conversation->id);
        } else {
            echo 'ok';
        }
    }

    public function actionMessageSend() {
        if (!isset($_POST['id']) || !$this->checkAccess($_POST['id'])) {
            return Yii::$app->getResponse()->redirect('conversation-list');
        }

        $conversation = Conversation::find($_POST['id']);
        $message      = new Message();

        $message->conversation_id = $conversation->id;
        $message->user_id         = Yii::$app->getUser()->getIdentity()->id;
        $message->body            = $_POST['body'];

        $message->save();

        $status = count($message->errors) > 0 ? 'error' : 'ok';

        $result = array(
            'status'  => $status,
            'errors'  => $message->errors,
            'message' => $message->toArray()
        );
        return json_encode($result);
    }
}
