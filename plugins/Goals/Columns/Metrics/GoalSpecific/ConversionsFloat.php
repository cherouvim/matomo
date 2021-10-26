<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Goals\Columns\Metrics\GoalSpecific;

use Piwik\DataTable\Row;
use Piwik\Metrics;
use Piwik\Piwik;
use Piwik\Plugins\Goals\Columns\Metrics\GoalSpecificProcessedMetric;
use Piwik\Plugins\Goals\Goals;

/**
 * The conversions for a specific goal. Returns the conversions for a single goal which
 * is then treated as a new column. Float version to allow partial attribution of
 * conversions to pages.
 */
class ConversionsFloat extends GoalSpecificProcessedMetric
{
    public function getName()
    {
        return Goals::makeGoalColumn($this->idGoal, 'nb_conversions_float', false);
    }

    public function getTranslatedName()
    {
        return Piwik::translate('Goals_Conversions', $this->getGoalNameForDocs());
    }

    public function getDependentMetrics()
    {
        return array('goals');
    }

    public function compute(Row $row)
    {
        $mappingFromNameToIdGoal = Metrics::getMappingFromNameToIdGoal();

        $goalMetrics = $this->getGoalMetrics($row);
        return $this->getMetric($goalMetrics, 'nb_conversions_float', $mappingFromNameToIdGoal);
    }
}