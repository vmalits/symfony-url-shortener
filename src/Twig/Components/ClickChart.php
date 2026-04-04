<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Domain\Click\Repository\ClickRepositoryInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('click_chart')]
final class ClickChart
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public int $days = 7;

    public function mount(int $days = 7): void
    {
        $this->days = max(1, min(365, $days));
    }

    public function __construct(
        private readonly ClickRepositoryInterface $clickRepository,
        private readonly ChartBuilderInterface $chartBuilder,
    ) {
    }

    public function getChart(): Chart
    {
        $data = $this->clickRepository->countByDay($this->days);

        $labels = [];
        $values = [];

        for ($i = $this->days - 1; $i >= 0; --$i) {
            $date = new \DateTimeImmutable("-{$i} days");
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $values[] = $data[$key] ?? 0;
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Clicks',
                'data' => $values,
                'borderColor' => 'rgb(79, 70, 229)',
                'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                'fill' => true,
                'tension' => 0.4,
            ]],
        ]);

        $chart->setOptions([
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1],
                ],
            ],
        ]);

        return $chart;
    }
}
