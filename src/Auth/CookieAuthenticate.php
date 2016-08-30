<?php

namespace JustinAtack\Authenticate\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;

class CookieAuthenticate extends BaseAuthenticate
{
    /**
     * Default config for this object.
     *
     * - `fields` The fields to use to identify a user by.
     * - `userModel` The alias for users table, defaults to Users.
     * - `finder` The finder method to use to fetch user record. Defaults to 'all'.
     *   You can set finder name as string or an array where key is finder name and value
     *   is an array passed to `Table::find()` options.
     *   E.g. ['finderName' => ['some_finder_option' => 'some_value']]
     * - `passwordHasher` Password hasher class. Can be a string specifying class name
     *    or an array containing `className` key, any other keys will be passed as
     *    config to the class. Defaults to 'Default'.
     * - Options `scope` and `contain` have been deprecated since 3.1. Use custom
     *   finder instead to modify the query to fetch user record.
     *
     * @var array
     */
    // protected $_defaultConfig = [
    //     'fields' => [
    //         'username' => 'username',
    //         'password' => 'password'
    //     ],
    //     'cookie' => [
    //         'name' => 'RememberMe',
    //     ],
    //     'userModel' => 'Users',
    //     'scope' => [],
    //     'finder' => 'all',
    //     'contain' => null,
    //     'passwordHasher' => 'Default'
    // ];

    protected function checkCookie()
    {
        /**
         * Check cookie exists in request
         *
         */
        if (!$this->_registry->Cookie->check($this->_config['cookie']['name'])) {
            return false;
        }

        /**
         * Check cookie contains correct keys
         *
         */
        foreach ($this->_config['fields'] as $field) {
            $key = $this->_config['cookie']['name'] . '.' . $field;
            if (!$this->_registry->Cookie->check($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Authenticate a user based on the request information.
     *
     * @param \Cake\Network\Request $request Request to get authentication information from.
     * @param \Cake\Network\Response $response A response object that can have headers added.
     * @return mixed Either false on failure, or an array of user data on success.
     */
    public function authenticate(Request $request, Response $response)
    {
        $this->checkCookie();
        $cookie = $this->_registry->Cookie->read($this->_config['cookie']['name']);

        return $this->_findUser(
            $cookie[$this->_config['fields']['username']],
            $cookie[$this->_config['fields']['password']]
        );
    }

    /**
     * Returns a list of all events that this authenticate class will listen to.
     *
     * An authenticate class can listen to following events fired by AuthComponent:
     *
     * - `Auth.afterIdentify` - Fired after a user has been identified using one of
     *   configured authenticate class. The callback function should have signature
     *   like `afterIdentify(Event $event, array $user)` when `$user` is the
     *   identified user record.
     *
     * - `Auth.logout` - Fired when AuthComponent::logout() is called. The callback
     *   function should have signature like `logout(Event $event, array $user)`
     *   where `$user` is the user about to be logged out.
     *
     * @return array List of events this class listens to. Defaults to `[]`.
     */
    public function implementedEvents()
    {
        return [
            // 'Auth.afterIdentify' => '_login',
            'Auth.logout' => '_logout'
        ];
    }

    /**
     * _login method
     * @param  Event  $event
     * @param  array  $user
     * @return redirection
     */
    // public function _login(Event $event, array $user)
    // {
    //     if ($user) {
    //         $this->_registry->Auth->setUser($user);
    //         return $this->redirect($this->_registry->Auth->redirectUrl());
    //     }
    //     $this->_registry->Flash->error(__('Invalid cookie credentials. Please try again.'));
    //     return $this->redirect($this->_registry->Auth->redirectUrl());
    // }

    /**
     * _logout method
     * @return delete cookie
     */
    public function _logout()
    {
        $this->_registry->Cookie->delete($this->_config['cookie']['name']);
    }
}
