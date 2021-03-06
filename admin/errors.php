<?php declare(strict_types=1);

/**
 * ****************************************************************************
 *  - A Project by Developers TEAM For Xoops - ( https://xoops.org )
 * ****************************************************************************
 *  XHTTPERROR - MODULE FOR XOOPS
 *  Copyright (c) 2007 - 2012
 *  Rota Lucio ( http://luciorota.altervista.org/xoops/ )
 *
 *  You may not change or alter any portion of this comment or credits
 *  of supporting developers from this source code or any supporting
 *  source code which is considered copyrighted (c) material of the
 *  original comment or credit authors.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  ---------------------------------------------------------------------------
 * @copyright  Rota Lucio ( http://luciorota.altervista.org/xoops/ )
 * @license    GNU General Public License v3.0
 * @package    xhttperror
 * @author     Rota Lucio ( lucio.rota@gmail.com )
 *
 *  $Rev$:     Revision of last commit
 *  $Author$:  Author of last commit
 *  $Date$:    Date of last commit
 * ****************************************************************************
 */

use Xmf\Module\Admin;
use Xmf\Request;
/** @var Helper $helper */

$currentFile = basename(__FILE__);
require_once __DIR__ . '/admin_header.php';
$op = $_REQUEST['op'] ?? (isset($_REQUEST['error_id']) ? 'edit_error' : 'list_errors');

// load classes
$errorHandler  = $helper->getHandler('Error');
$reportHandler = $helper->getHandler('Report');

// count errors
$countErrors = $errorHandler->getCount();

switch ($op) {
    default:
    case 'list_errors':
        // render start here
        xoops_cp_header();
        // render submenu
        $adminObject = Admin::getInstance();
        $adminObject->displayNavigation(basename(__FILE__));
        $adminObject->addItemButton(_AM_XHTTPERR_ERROR_ADD, '' . $currentFile . '?op=edit_error', 'add');
        $adminObject->displayButton('left');

        if ($countErrors > 0) {
            $errorCriteria = new \CriteriaCompo();
            $errorCriteria->setSort('error_statuscode');
            $errorCriteria->setOrder('ASC');
            $errors = $errorHandler->getObjects($errorCriteria, true, false); // as array

            $GLOBALS['xoopsTpl']->assign('errors', $errors);
            $GLOBALS['xoopsTpl']->assign('token', $GLOBALS['xoopsSecurity']->getTokenHTML());
            $GLOBALS['xoopsTpl']->display('db:xhttperror_admin_errors_list.tpl');
        } else {
            echo _AM_XHTTPERR_ERROR_NOERRORS;
        }

        require_once __DIR__ . '/admin_footer.php';
        break;

    case 'edit_error':
    case 'new_error':
        // render start here
        xoops_cp_header();
        // render submenu
        $adminObject = Admin::getInstance();
        $adminObject->displayNavigation(basename(__FILE__));
        $adminObject->addItemButton(_AM_XHTTPERR_ERROR_LIST, '' . $currentFile . '?op=list_errors', 'list');
        $adminObject->displayButton('left');

        if (Request::hasVar('error_id', 'REQUEST')) {
            $errorObj = $errorHandler->get($_REQUEST['error_id']);
        } else {
            $errorObj = $errorHandler->create();
        }
        $formObj = $errorObj->getForm();
        $formObj->display();

        require_once __DIR__ . '/admin_footer.php';
        break;

    case 'save_error':
        if (!$GLOBALS['xoopsSecurity']->check()) {
            redirect_header($currentFile, 3, implode(',', $GLOBALS['xoopsSecurity']->getErrors()));
        }
        if (Request::hasVar('error_id', 'REQUEST')) {
            $errorObj = $errorHandler->get($_REQUEST['error_id']);
        } else {
            $errorObj = $errorHandler->create();
        }
        // Check statuscode
        if (Request::hasVar('error_statuscode', 'REQUEST')) {
            $errorCriteria = new \CriteriaCompo();
            $errorCriteria->add(new \Criteria('error_statuscode', $_REQUEST['error_statuscode']));
            if ($errorHandler->getCount($errorCriteria) > 0) {
                redirect_header($currentFile, 3, _AM_XHTTPERR_STATUSCODE_EXISTS);
            } else {
                $errorObj->setVar('error_statuscode', $_REQUEST['error_statuscode']);
            }
        }
        $errorObj->setVar('error_title', $_REQUEST['error_title']);
        $errorObj->setVar('error_text', $_REQUEST['error_text']);
        $errorObj->setVar('error_text_html', $_REQUEST['error_text_html']);
        $errorObj->setVar('error_text_smiley', $_REQUEST['error_text_smiley']);
        $errorObj->setVar('error_text_breaks', $_REQUEST['error_text_breaks']);
        $errorObj->setVar('error_showme', $_REQUEST['error_showme']);
        $errorObj->setVar('error_redirect', $_REQUEST['error_redirect']);
        $errorObj->setVar('error_redirect_time', \Xmf\Request::getInt('error_redirect_time', 0, 'REQUEST'));
        /* IN PROGRESS
        $errorObj->setVar('error_redirect_message', (int) $_REQUEST['error_redirect_message']);
        */
        $errorObj->setVar('error_redirect_uri', $_REQUEST['error_redirect_uri']);

        if ($errorHandler->insert($errorObj)) {
            redirect_header($currentFile, 3, _AM_XHTTPERR_SAVEDSUCCESS);
        } else {
            redirect_header($currentFile, 3, _AM_XHTTPERR_NOTSAVED);
        }
        break;
        
    case 'delete_error':
        $errorObj = $errorHandler->get($_REQUEST['error_id']);
        if (Request::hasVar('ok', 'REQUEST') && 1 == $_REQUEST['ok']) {
            if (!$GLOBALS['xoopsSecurity']->check()) {
                redirect_header($currentFile, 3, implode(',', $GLOBALS['xoopsSecurity']->getErrors()));
            }
            if ($errorHandler->delete($errorObj)) {
                redirect_header($currentFile, 3, _AM_XHTTPERR_DELETEDSUCCESS);
            } else {
                echo $errorObj->getHtmlErrors();
            }
        } else {
            // render start here
            xoops_cp_header();
            xoops_confirm(
                [
                    'ok' => 1, 
                    'error_id' => $_REQUEST['error_id'], 
                    'op' => 'delete_error'
                ], 
                $_SERVER['REQUEST_URI'], 
                sprintf(_AM_XHTTPERR_ERROR_RUSUREDEL, $errorObj->getVar('error_title'))
            );
            xoops_cp_footer();
        }
        break;
}
