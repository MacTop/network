<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Sophie
 * Date: 22.07.13
 * Time: 0:05
 * To change this template use File | Settings | File Templates.
 */

namespace app\controllers;

use yii\base\Exception;
use yii\console\Controller;
use app\components\Fixtures;

class FixtureController extends Controller
{
    // Default count of data for generating
    const USERS_COUNT = 10;
    const CONVERSATIONS_COUNT = 20;
    const MESSAGES_COUNT = 50;

    /**
     * @var object fixtures
     */
    private $fixture;

    /**
     * @var string the default command action.
     */
    public $defaultAction = 'all';

    /**
     * Create fixture object
     * @return void
     */
    public function init() {
        $this->fixture = new Fixtures();
    }
    // TODO: implement conversations and messages actions when they'll be implemented in fixture component

    /**
     * Creates users, conversations and members
     */
    public function actionAll($usersCount = self::USERS_COUNT, $conversationsCount = self::CONVERSATIONS_COUNT, $messagesCount = self::MESSAGES_COUNT) {
        $this->fixture->generateUsers($usersCount);
    }

    /**
     * Create users
     * @param $usersCount
     */
    public function actionUsers($usersCount = self::USERS_COUNT) {
        $this->fixture->generateUsers($usersCount);
    }

    /**
     * Create conversations
     * @param $conversationsCount
     */
    public function actionConversations($conversationsCount = self::CONVERSATIONS_COUNT) {

    }

    /**
     * Create messages
     * @param $messagesCount
     */
    public function actionMessages($messagesCount = self::MESSAGES_COUNT) {

    }
}