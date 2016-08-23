# Passwordless Authentication for CakePHP 3

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install this composer package is:

```
composer require justinatack/authenticate:dev-master
```

In your config/bootstrap.php file add the following

```
Plugin::load('JustinAtack/Authenticate');
```

In your src/Controller/AppController.php file add the following

```
$this->loadComponent('Auth', [
    'authenticate' => [
        'JustinAtack/Authenticate.Passwordless' => [
            'fields' => [
                'username' => 'email',
                'token' => 'token',
                'token_expiry' => 'token_expiry'
            ],
            'token' => [
                'query' => 'token',
                'length' => 10, // bytes
                'expires' => '+10 mins'
            ]
        ]
    ]
]);
```

## Instructions

Create a Users table with ```email```, ```token``` and ```token_expiry``` fields. The following columns could be used as a starting point in a Users migration.
```
$table->addColumn('email', 'string', [
    'default' => null,
    'limit' => 255,
    'null' => false,
]);
$table->addColumn('token', 'string', [
    'default' => null,
    'limit' => 255,
    'null' => true,
]);
$table->addColumn('token_expiry', 'datetime', [
    'default' => null,
    'null' => true,
]);
```

In your src/Controller/UsersController.php add the following login method
```
public function login($token = null)
{
    /**
     * Validate token and login user
     *
     */
    if (!empty($this->request->params['pass'][0]) || !empty($this->request->query('token'))) {
        $user = $this->Auth->identify();
        if ($user) {
            $this->Auth->setUser($user);
            return $this->redirect($this->Auth->redirectUrl());
        }
        $this->Flash->error(__('Invalid or expired token, please request new login token'));
        return $this->redirect($this->Auth->redirectUrl());
    }

    /**
     * Validate email and send login token
     *
     */
    if ($this->request->is('post')) {
        $user = $this->Auth->identify();
        if ($user) {
            $this->Flash->success(__('A one-time login URL has been emailed to you'));
            return $this->redirect($this->Auth->redirectUrl());
        }
        $this->Flash->error(__('Email is incorrect'));
        return $this->redirect($this->Auth->redirectUrl());
    }
}
```

Create a login view with a single email form field and submit button. Now add a User and then submit the Login form with email address. This will generate a token and set the token_expiry. Using this code you can login with the following urls, both will work the same.
```
https://www.example.com/users/login/{token_here}
https://www.example.com/users/login?token={token_here}
```

At this point you should have a working login system with ```token``` and ```token_expiry``` being saved after each login email request. This plugin does not handle the token email to send to the User after login request. This part is for you to decide how to handle, perhaps you might want to Queue the request or email it straight away or even SMS it. Heres a head start. The following code is placed in your src/Controller/UsersController.php file. It listens to the Auth.afterIdentify event. You can trigger your own method call to do as you please e.g. send the token email with login link to your user. I have simply logged the event to my debug log.

```
use Cake\Log\Log;

/**
 * Initialize method
 *
 */
public function initialize()
{
    parent::initialize();
    $this->Auth->allow(['login', 'logout', 'add']);
    $this->eventManager()->on('Auth.afterIdentify', [$this, 'afterIdentify']);
}

public function afterIdentify(Event $event, array $user)
{
    Log::write('debug', $user);
    // Email user link with embedded token.
    // See example links above to generate correct URLs
}
```

Example debug log output
```
2016-08-22 07:18:45 Debug: Array
(
    [id] => 1
    [email] => passwordless@example.com
    [token] => 53af7103f12c1e9ff752
)
```

## Warning
All token links should ONLY be used over a secure SSL connection.
