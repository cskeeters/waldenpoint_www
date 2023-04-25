<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Twig\Extra\String\StringExtension;
use Psr\Container\ContainerInterface;

use Symfony\Component\Yaml\Yaml; /* symfony/yaml */

require __DIR__ . '/../vendor/autoload.php';

/* Secrets should be kept in app/.env */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();

//With default settings
$app = AppFactory::create();

$twig = Twig::create('../view', ['cache' => false]);

$app->add(TwigMiddleware::create($app, $twig));

/**
 * The routing middleware should be added earlier than the ErrorMiddleware
 * Otherwise exceptions thrown from it will not be handled by the middleware
 */
$app->addRoutingMiddleware();

/**
 * Add Error Middleware
 *
 * @param bool                  $displayErrorDetails -> Should be set to false in production
 * @param bool                  $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool                  $logErrorDetails -> Display error details in error log
 * @param LoggerInterface|null  $logger -> Optional PSR-3 Logger
 *
 * Note: This middleware should be added last. It will not handle any exceptions/errors
 * for middleware added after it.
 */
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Returns true on valid captcha
function reCAPTCHA(string $g_recaptcha_response) {
    define("RECAPTCHA_V3_SECRET_KEY", $_ENV['RECAPTCHA_V3_SECRET_KEY']);

    $query = http_build_query(array('secret' => RECAPTCHA_V3_SECRET_KEY, 'response' => $g_recaptcha_response));

    // call curl to POST request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $google_response = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($google_response, true);

    // verify the response
    if ($json["success"] == '1' && $json["score"] >= 0.5) {
        return true;
    }
    return false;
}

// Calls reCAPTCHA and sets human in session if true
function set_human(string $g_recaptcha_response) {

    if (reCAPTCHA($g_recaptcha_response)) {
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['human'] = 1;
        return true;
    }
    return false;
}

$app->get('/ledger', function (Request $request, Response $response, array $args) {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!array_key_exists('passcode', $_SESSION)) {
        $response = $response->withStatus(302);
        $response = $response->withHeader('Location', '/passcode?return=/ledger');
        return $response;
    }

    if ($_SESSION['passcode'] != $_ENV["MEMBER_PASSCODE"]) {
        $data = ['return' => '/ledger'];
        $response = Twig::fromRequest($request)->render($response, 'bad_passcode.twig', $data);
        return $response;
    }


    $data = file_get_contents(__DIR__.'/../waldenpoint_accounting/waldenpoint.beancount');
    if ($data == false) {
        $response = $response->withStatus(404);
        return $response;
    }

    $response = $response->withHeader('Content-Type', 'text/plain');
    $response->getBody()->write($data);
    return $response;
});

$app->get('/beans/[{params:.*}]', function (Request $request, Response $response, array $args) {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!array_key_exists('passcode', $_SESSION)) {
        $response = $response->withStatus(302);
        $response = $response->withHeader('Location', '/passcode?return=/beans/index.html');
        return $response;
    }

    if ($_SESSION['passcode'] != $_ENV["MEMBER_PASSCODE"]) {
        $data = ['return' => '/beans/index.html'];
        $response = Twig::fromRequest($request)->render($response, 'bad_passcode.twig', $data);
        return $response;
    }


    $path = $args['params'];
    $data = file_get_contents(__DIR__.'/../beans/'.$path);
    if ($data == false) {
        $response = $response->withStatus(404);
        return $response;
    }

    $response->getBody()->write($data);
    return $response;
});

$app->get('/', function (Request $request, Response $response, array $args) {
    $response = Twig::fromRequest($request)->render($response, 'index.twig', []);
    return $response;
});

$app->get('/filing-agencies', function (Request $request, Response $response, array $args) {
    /* $response->getBody()->write("Hello, world"); */
    $response = Twig::fromRequest($request)->render($response, 'filing-agencies.twig', []);
    return $response;
});

$app->get('/contact', function (Request $request, Response $response, array $args) {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!array_key_exists('human', $_SESSION)) {
        $response = $response->withStatus(302);
        $response = $response->withHeader('Location', '/captcha?return=/contact');
        return $response;
    }

    // This enables the removal of names and contact information from the github page
    $lots = Yaml::parseFile(__DIR__.'/../lots.yaml');
    // var_dump($lots['Lot 4'][0]);
    $data = [];
    $contacts = [];

    // These need to be edited when new board members are elected
    $contact = $lots['Lot 4'][0];
    $contact['position'] = "President / Treasurer / Webmaster";
    array_push($contacts, $contact);

    $contact = $lots['Lot 13'][0];
    $contact['position'] = "Secretary";
    array_push($contacts, $contact);

    $contact = $lots['Lot 3'][0];
    $contact['position'] = "Board Member / City Relations";
    array_push($contacts, $contact);

    $data['contacts'] = $contacts;

    $response = Twig::fromRequest($request)->render($response, 'contact.twig', $data);
    return $response;
});

$app->get('/members', function (Request $request, Response $response, array $args) {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!array_key_exists('passcode', $_SESSION)) {
        $response = $response->withStatus(302);
        $response = $response->withHeader('Location', '/passcode?return=/members');
        return $response;
    }

    if ($_SESSION['passcode'] == $_ENV["MEMBER_PASSCODE"]) {
        $lots = Yaml::parseFile(__DIR__.'/../lots.yaml');
        /* var_dump($lots); */
        $data = ['lots' => $lots];
        ;
        $data['Treasurer'] = $lots['Lot 4'][0];
        $response = Twig::fromRequest($request)->render($response, 'members.twig', $data);
        return $response;
    }

    $data = ['return' => '/members'];
    $response = Twig::fromRequest($request)->render($response, 'bad_passcode.twig', $data);
    return $response;
});

$app->get('/passcode', function (Request $request, Response $response, array $args) {
    $query = $request->getQueryParams(); //Array
    $ret = '/';
    if (array_key_exists('return', $query)) {
        $ret = $query['return'];
    }
    $data = ['return' => $ret];
    $response = Twig::fromRequest($request)->render($response, 'passcode.twig', $data);
    return $response;
});

$app->post('/passcode', function (Request $request, Response $response, array $args) {

    $form = $request->getParsedBody();

    if (set_human($form['g-recaptcha-response'])) {
        // Set it no matter what it is
        $_SESSION['passcode'] = $form['passcode'];

        $ret = $request->getParsedBody()['return'];
        if ($ret != '') {
            $response = $response->withStatus(302);
            $response = $response->withHeader('Location', $ret);
            return $response;
        }
        $response = $response->withStatus(302);
        $response = $response->withHeader('Location', '/');
        return $response;
    }

    $response = Twig::fromRequest($request)->render($response, 'captcha-fail.twig', []);
    return $response;
});

$app->get('/captcha', function (Request $request, Response $response, array $args) {
    $ret = $request->getQueryParams()['return'];
    $data = ['return' => $ret];
    $response = Twig::fromRequest($request)->render($response, 'captcha.twig', $data);
    return $response;
});

$app->post('/captcha', function (Request $request, Response $response, array $args) {

    $form = $request->getParsedBody();

    if (set_human($form['g-recaptcha-response'])) {
        $ret = $request->getParsedBody()['return'];
        if ($ret != '') {
            $response = $response->withStatus(302);
            $response = $response->withHeader('Location', $ret);
        } else {
            $response->getBody()->write("Return not set");
        }
    }

    $response = Twig::fromRequest($request)->render($response, 'captcha-fail.twig', []);
    return $response;
});

$app->get('/doc/{name}', function (Request $request, Response $response, array $args) {
    $terms = [];
    $terms['fee'] = 'an estate of land, especially one held on condition of feudal service';
    $terms['lender'] = 'the bank or lender that is using the lot as collateral';

    try {
        $name = $args['name'];
        $response = Twig::fromRequest($request)->render($response, "doc/$name.twig", ['terms' => $terms]);
        return $response;
    }
    catch (Exception $e) {
        $response = $responseFactory->createResponse(404);
        return $response;
        //code to handle the exception
    }
});

$app->run();
?>
