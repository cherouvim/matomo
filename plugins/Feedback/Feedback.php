<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Feedback;

use Piwik\Date;
use Piwik\Plugins\UsersManager\API as APIUsersManager;
use Piwik\Plugins\UsersManager\UsersManager;
use Piwik\Site;
use Piwik\View;
use Piwik\Piwik;
use Piwik\Common;
use Piwik\Plugin\Manager as PluginManager;
use Piwik\Plugins\Feedback\FeedbackReminder;

/**
 *
 */
class Feedback extends \Piwik\Plugin
{
    const NEVER_REMIND_ME_AGAIN = "-1";

    /**
     * @see \Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'AssetManager.getStylesheetFiles'        => 'getStylesheetFiles',
            'AssetManager.getJavaScriptFiles'        => 'getJsFiles',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'Controller.CoreHome.index.end'          => 'renderViewsAndAddToPage'
        );
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/Feedback/stylesheets/feedback.less";
        $stylesheets[] = "plugins/Feedback/vue/src/RateFeature/RateFeature.less";
        $stylesheets[] = "plugins/Feedback/vue/src/FeedbackQuestion/FeedbackQuestion.less";
    }

    public function getJsFiles(&$jsFiles)
    {
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'Feedback_ThankYou';
        $translationKeys[] = 'Feedback_RateFeatureTitle';
        $translationKeys[] = 'Feedback_RateFeatureThankYouTitle';
        $translationKeys[] = 'Feedback_RateFeatureLeaveMessageLike';
        $translationKeys[] = 'Feedback_RateFeatureLeaveMessageDislike';
        $translationKeys[] = 'Feedback_SendFeedback';
        $translationKeys[] = 'Feedback_RateFeatureSendFeedbackInformation';
        $translationKeys[] = 'Feedback_ReviewMatomoTitle';
        $translationKeys[] = 'Feedback_PleaseLeaveExternalReviewForMatomo';
        $translationKeys[] = 'Feedback_RemindMeLater';
        $translationKeys[] = 'Feedback_NeverAskMeAgain';
        $translationKeys[] = 'Feedback_WontShowAgain';
        $translationKeys[] = 'General_Ok';
        $translationKeys[] = 'General_Cancel';
        $translationKeys[] = 'Feedback_Question0';
        $translationKeys[] = 'Feedback_Question1';
        $translationKeys[] = 'Feedback_Question2';
        $translationKeys[] = 'Feedback_Question3';
        $translationKeys[] = 'Feedback_Question4';
        $translationKeys[] = 'Feedback_FeedbackTitle';
        $translationKeys[] = 'Feedback_FeedbackSubtitle';
        $translationKeys[] = 'Feedback_ThankYourForFeedback';
        $translationKeys[] = 'Feedback_Policy';
        $translationKeys[] = 'Feedback_ThankYourForFeedback';
        $translationKeys[] = 'Feedback_ThankYou';
        $translationKeys[] = 'Feedback_MessageBodyValidationError';
    }

    public function renderViewsAndAddToPage(&$pageHtml)
    {
        //only show on superuser
        if (!Piwik::hasUserSuperUserAccess()) {
            return $pageHtml;
        }
        $feedbackQuestionBanner = $this->renderFeedbackQuestion();

        $matches = preg_split('/(<body.*?>)/i', $pageHtml, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $pageHtml = $matches[0] . $matches[1] . $feedbackQuestionBanner . $matches[2];
    }


    public function renderFeedbackQuestion()
    {
        $feedbackQuestionBanner = new View('@Feedback/feedbackQuestionBanner');
        $feedbackQuestionBanner->showQuestionBanner = (int)$this->getShouldPromptForFeedback();

        return $feedbackQuestionBanner->render();
    }

    public function getShouldPromptForFeedback()
    {
        if (Piwik::isUserIsAnonymous()) {
            return false;
        }

        // Hide Feedback popup in all tests except if forced
        if ($this->isDisabledInTestMode()) {
            return false;
        }

        $feedbackReminder = new FeedbackReminder();
        $nextReminderDate = $feedbackReminder->getUserOption();
        $now = Date::now()->getTimestamp();

        //user answered question
        if ($nextReminderDate === self::NEVER_REMIND_ME_AGAIN) {
            return false;
        }

        // if is new user or old user field not exist
        if ($nextReminderDate === false || $nextReminderDate <= 0) {

            // if user is created more than 6 month ago, set reminder to today and show banner
            $userCreatedDate = Piwik::getCurrentUserCreationData();
            if (!empty($userCreatedDate) && Date::factory($userCreatedDate)->addMonth(6)->getTimestamp() < $now) {
                $nextReminder = Date::now()->getStartOfDay()->subDay(1)->toString('Y-m-d');
                $feedbackReminder->setUserOption($nextReminder);
                return true;
            }
            //new user extend to 6 month, don't show banner
            $nextReminder = Date::now()->getStartOfDay()->addMonth(6)->toString('Y-m-d');
            $feedbackReminder->setUserOption($nextReminder);
            return false;
        }

        $nextReminderDate = Date::factory($nextReminderDate);
        if ($nextReminderDate->getTimestamp() > $now) {
            return false;
        }
        return true;

    }

    // needs to be protected not private for testing purpose
    protected function isDisabledInTestMode()
    {
        return defined('PIWIK_TEST_MODE') && PIWIK_TEST_MODE && !Common::getRequestVar('forceFeedbackTest', false);
    }

}
