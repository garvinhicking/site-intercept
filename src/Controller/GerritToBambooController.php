<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Exception\DoNotCareException;
use App\Extractor\GerritCorePreMergePushEvent;
use App\Service\BambooService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Called by core gerrit review system (review.typo3.org) as patchset-created hook.
 * Triggers a new bamboo pre-merge master or pre-merge v8 or similar
 * run that applies the given change and patch set and runs tests.
 */
class GerritToBambooController extends AbstractController
{
    /**
     * @Route("/gerrit", name="gerrit_to_bamboo")
     * @Route("/", host="intercept.typo3.com", name="legacy_gerrit_to_bamboo")
     * @Route("/index.php", host="intercept.typo3.com", name="legacy2_gerrit_to_bamboo")
     * @param Request $request
     * @param BambooService $bambooService
     * @return Response
     */
    public function index(Request $request, BambooService $bambooService): Response
    {
        try {
            $pushInformation = new GerritCorePreMergePushEvent($request);
            $bambooService->triggerNewCoreBuild($pushInformation);
        } catch (DoNotCareException $e) {
            // Do not care if pushed to some other branch than the
            // ones we do want to handle.
        }
        return Response::create();
    }
}
