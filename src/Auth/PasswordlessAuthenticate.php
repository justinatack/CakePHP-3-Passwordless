<?php

namespace JustinAtack\Authenticate\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Cake\Chronos\Chronos;

class PasswordlessAuthenticate extends BaseAuthenticate
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
    //         'username' => 'email',
    //         'token' => 'token', // varchar(255)
    //         'token_expiry' => 'token_expiry' // datetime
    //     ],
    //     'token' => [
    //         'query' => 'token',
    //         'length' => 32, // bytes
    //         'expires' => '+10 mins'
    //     ],
    //     'userModel' => 'Users',
    //     'scope' => [],
    //     'finder' => 'all',
    //     'contain' => null
    // ];

    /**
     * Find a user record using the username provided.
     *
     * @param string $username The username/identifier.
     * @return bool|array Either false on failure, or an array of user data.
     */
    protected function _findUser($username, $password = null)
    {
        $result = $this->_userQuery($username)->first();

        if (empty($result)) {
            return false;
        }

        $result = $result->toArray();

        // Set token and attach to return array
        $token = $this->_setToken($username);
        $result[$this->_config['fields']['token']] = $token;

        return $result;
    }

    /**
     * Get query object for fetching user from database.
     *
     * @param string $username The username/identifier.
     * @return \Cake\ORM\Query
     */
    protected function _userQuery($username)
    {
        $config = $this->_config;
        $table = TableRegistry::get($config['userModel']);

        $options = [
            'conditions' => [$table->aliasField($config['fields']['username']) => $username]
        ];

        if (!empty($config['scope'])) {
            $options['conditions'] = array_merge($options['conditions'], $config['scope']);
        }
        if (!empty($config['contain'])) {
            $options['contain'] = $config['contain'];
        }

        $finder = $config['finder'];
        if (is_array($finder)) {
            $options += current($finder);
            $finder = key($finder);
        }

        if (!isset($options['username'])) {
            $options['username'] = $username;
        }

        $query = $table->find($finder, $options);

        return $query;
    }

    /**
     * Generate and save User token
     *
     * @return string token value
     */
    protected function _setToken($username)
    {
        $config = $this->_config;
        $table = TableRegistry::get($config['userModel']);
        $data = [
            $config['fields']['token'] => bin2hex(Security::randomBytes($config['token']['length'])),
            $config['fields']['token_expiry'] => Chronos::parse($config['token']['expires'])
        ];
        $conditions = [$config['fields']['username'] => $username];
        $table->updateAll($data, $conditions);

        return $data[$config['fields']['token']];
    }

    /**
     * Find token and check it is valid
     *
     * @param string $token The token/identifier.
     * @return bool|array Either false on failure, or an array of user data.
     */
     protected function _findToken($token)
     {
         $result = $this->_tokenQuery($token)->first();

         if (empty($result)) {
             return false;
         }

         return $result->toArray();
     }

    /**
     * Get query object by finding user with valid token
     *
     * @param  string $token
     * @return @return \Cake\ORM\Query
     */
    protected function _tokenQuery($token)
    {
        $config = $this->_config;
        $table = TableRegistry::get($config['userModel']);

        $options = [
            'conditions' => [
                $table->aliasField($config['fields']['token']) => $token,
                $table->aliasField($config['fields']['token_expiry']) . ' >=' => Chronos::now()
            ]
        ];

        if (!empty($config['scope'])) {
            $options['conditions'] = array_merge($options['conditions'], $config['scope']);
        }
        if (!empty($config['contain'])) {
            $options['contain'] = $config['contain'];
        }

        $finder = $config['finder'];
        if (is_array($finder)) {
            $options += current($finder);
            $finder = key($finder);
        }

        $query = $table->find($finder, $options);

        return $query;
    }

    public function authenticate(Request $request, Response $response)
    {
        // Check param e.g. users/login/{token}
        if (!empty($request->params['pass'][0])) {
            return $this->_findToken($request->params['pass'][0]);
        }

        // Check query string e.g. users/login?token={token}
        if (!empty($request->query($this->_config['token']['query']))) {
            return $this->_findToken($request->query($this->_config['token']['query']));
        }

        // Check form post data users/login
        if (!empty($request->data($this->_config['fields']['username']))) {
            return $this->_findUser($request->data($this->_config['fields']['username']));
        }

        return false;
    }
}
