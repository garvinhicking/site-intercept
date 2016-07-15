<?php
declare(strict_types = 1);

namespace T3G\Intercept;

use Monolog\Logger;

/**
 * Class RequestDispatcher
 *
 * Dispatches to controller->actions depending on $_REQUEST parameters
 *
 * @package T3G\Intercept
 */
class RequestDispatcher
{
    use Traits\Logger;

    /**
     * @var \T3G\Intercept\InterceptController
     */
    private $interceptController;
    /**
     * @var \T3G\Intercept\GithubToGerritController
     */
    private $githubToGerritController;

    public function __construct(
        InterceptController $interceptController = null,
        GithubToGerritController $githubToGerritController = null,
        Logger $logger = null
    ) {
        $this->interceptController = $interceptController ?: new InterceptController();
        $this->githubToGerritController = $githubToGerritController ?: new GithubToGerritController();
        $this->setLogger($logger);
    }

    public function dispatch()
    {
        try {
            if (!empty($_GET['github'])) {
                $this->githubToGerritController->transformPullRequestToGerritReview(file_get_contents("php://input"));
            } else {
                if (!empty($_POST['payload'])) {
                    $this->interceptController->postBuildAction();
                } else if (!empty($_POST['changeUrl']) && !empty($_POST['patchset']) && !empty($_POST['branch'])) {
                    $this->interceptController->newBuildAction();
                } else {
                    $this->logger->warning(
                        'Could not dispatch request. Request Data:' . "\n" . var_export($_REQUEST, true)
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('ERROR:"' . $e->getMessage() . '"" in ' . $e->getFile() . ' line ' . $e->getLine());
        }
    }
}