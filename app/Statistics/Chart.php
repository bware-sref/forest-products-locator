<?php

namespace App\Statistics;

use Illuminate\Support\Carbon;
use JsonSerializable;

/**
 * A chart for the Statistics pages: what <x-stats.chart> renders with Chart.js, plus the same numbers as a table.
 *
 * Colors are roles, not hex: series-1..8 (categorical, in fixed order), other (gray, for "All others"),
 * seq-NNN (one blue ramp, light to dark). public/assets/js/admin/statistics.js resolves them from CSS variables,
 * so light and dark mode each get their own validated steps.
 */
final class Chart implements JsonSerializable
{
    private string $type = 'bar';

    private ?string $description = null;

    private array $labels = [];

    /** @var list<array{label: string, data: list<int|float|null>, color: string, links?: list<?string>}> */
    private array $datasets = [];

    private bool $horizontal = false;

    private bool $stacked = false;

    private string $unit = '';

    private ?int $max = null;

    private int $height = 300;

    private string $labelHeading = '';

    private ?string $note = null;

    private ?string $empty = null;

    private bool $showTable = true;

    private function __construct(public readonly string $id, public readonly string $title) {}

    public static function bar(string $id, string $title): self
    {
        return new self($id, $title);
    }

    public static function line(string $id, string $title): self
    {
        $chart = new self($id, $title);
        $chart->type = 'line';

        return $chart;
    }

    /**
     * A tiny trend line with no axes, for stat tiles. Its numbers belong in a table elsewhere on the page.
     */
    public static function sparkline(string $id, string $title): self
    {
        $chart = new self($id, $title);
        $chart->type = 'sparkline';
        $chart->height = 40;
        $chart->showTable = false;

        return $chart;
    }

    public function describe(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Small print under the chart, e.g. what the data can't show.
     */
    public function note(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @param  array<int|string>  $labels
     */
    public function labels(array $labels, string $heading = ''): self
    {
        $this->labels = array_map('strval', array_values($labels));
        $this->labelHeading = $heading;

        return $this;
    }

    /**
     * Labels from 'Y-m' month keys, shown as "Jan 2026".
     */
    public function months(array $monthKeys): self
    {
        return $this->labels(array_map(fn ($key) => Carbon::createFromFormat('Y-m-d', "{$key}-01")->format('M Y'), $monthKeys), 'Month');
    }

    /**
     * Labels from 'Y-m-d' dates, shown as "Nov 4, 2026".
     */
    public function days(array $dates): self
    {
        return $this->labels(array_map(fn ($date) => Carbon::parse($date)->format('M j, Y'), $dates), 'Date');
    }

    /**
     * @param  array<int|float|null>  $data  one value per label
     * @param  string|null  $color  a color role; defaults to the next categorical slot
     * @param  array<?string>  $links  optional URL per label: clicking that bar goes there
     */
    public function series(string $label, array $data, ?string $color = null, array $links = []): self
    {
        $dataset = [
            'label' => $label,
            'data' => array_values($data),
            'color' => $color ?? 'series-'.(count($this->datasets) % 8 + 1),
        ];
        if ($links) {
            $dataset['links'] = array_values($links);
        }
        $this->datasets[] = $dataset;

        return $this;
    }

    public function horizontal(): self
    {
        $this->horizontal = true;

        return $this;
    }

    public function stacked(): self
    {
        $this->stacked = true;

        return $this;
    }

    /**
     * Values are percentages (0–100): axis capped at 100 and values shown with %.
     */
    public function percent(): self
    {
        $this->unit = '%';
        $this->max = 100;

        return $this;
    }

    public function height(int $pixels): self
    {
        $this->height = $pixels;

        return $this;
    }

    /**
     * Height for a horizontal bar chart: room for each bar plus axes.
     */
    public function fitBars(int $perBar = 28, int $minimum = 160): self
    {
        $this->height = max($minimum, count($this->labels) * $perBar + 60);

        return $this;
    }

    /**
     * Shown instead of the chart when there's nothing to plot.
     */
    public function whenEmpty(string $message): self
    {
        $this->empty = $message;

        return $this;
    }

    public function isEmpty(): bool
    {
        foreach ($this->datasets as $dataset) {
            foreach ($dataset['data'] as $value) {
                if ($value) {
                    return false;
                }
            }
        }

        return true;
    }

    public function withoutTable(): self
    {
        $this->showTable = false;

        return $this;
    }

    /**
     * The same numbers as a table: one row per label, one column per series.
     *
     * @return array{columns: list<string>, rows: list<list<string>>}
     */
    public function table(): array
    {
        $columns = [$this->labelHeading ?: ' '];
        foreach ($this->datasets as $dataset) {
            $columns[] = $dataset['label'];
        }

        $rows = [];
        foreach ($this->labels as $i => $label) {
            $row = [$label];
            foreach ($this->datasets as $dataset) {
                $value = $dataset['data'][$i] ?? null;
                $row[] = null === $value ? '–' : self::format($value).$this->unit;
            }
            $rows[] = $row;
        }

        return ['columns' => $columns, 'rows' => $rows];
    }

    public static function format(int|float $value): string
    {
        return is_float($value) && floor($value) !== $value
            ? number_format($value, 1)
            : number_format($value);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'note' => $this->note,
            'type' => $this->type,
            'labels' => $this->labels,
            'datasets' => $this->datasets,
            'horizontal' => $this->horizontal,
            'stacked' => $this->stacked,
            'unit' => $this->unit,
            'max' => $this->max,
            'height' => $this->height,
            'empty' => $this->empty,
            'isEmpty' => $this->isEmpty(),
            'showTable' => $this->showTable,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
