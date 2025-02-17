<?php

declare(strict_types=1);

namespace Cortex\Foundation\DataTables;

use Cortex\Foundation\Models\Log;
use Cortex\Foundation\Transformers\LogTransformer;

/**
 * @property \Spatie\Activitylog\Traits\CausesActivity $resource
 * @property string                                    $tabs
 * @property string                                    $id
 */
class LogsDataTable extends AbstractDataTable
{
    /**
     * {@inheritdoc}
     */
    protected $model = Log::class;

    /**
     * {@inheritdoc}
     */
    protected $transformer = LogTransformer::class;

    /**
     * Get the query object to be processed by dataTables.
     *
     * @TODO: Apply row selection and bulk actions, check parent::query() for reference.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = $this->resource->activities();

        return $this->scope()->applyScopes($query);
    }

    /**
     * Get default builder parameters.
     *
     * @return array
     */
    protected function getBuilderParameters(): array
    {
        $buttons = $this->getButtons();

        return [
            'dom' => $this->options['dom'],
            'keys' => $this->options['keys'],
            'mark' => $this->options['mark'],
            'order' => $this->options['order'],
            'select' => $this->options['select'],
            'retrieve' => $this->options['retrieve'],
            'autoWidth' => $this->options['autoWidth'],
            'fixedHeader' => $this->options['fixedHeader'],
            'responsive' => $this->options['responsive'],
            'stateSave' => $this->options['stateSave'],
            'scrollX' => $this->options['scrollX'],
            'pageLength' => $this->options['pageLength'],
            'lengthMenu' => $this->options['lengthMenu'],
            'buttons' => $buttons,
            'drawCallback' => "function (settings) {
                var api = this.api();

                $('#$this->id tbody td.dt-details-control').on('click', function () {
                    var tr = $(this).closest('tr');
                    var row = api.row(tr);

                    if (row.child.isShown()) {
                        row.child.hide();
                        tr.removeClass('shown');
                    } else {
                        row.child(dtFormatLogDetails(row.data().properties)).show();
                        tr.addClass('shown');
                    }
                });
            }",
        ];
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns(): array
    {
        return [
            'id' => ['checkboxes' => '{"selectRow": true}', 'exportable' => false, 'printable' => false],
            'details' => ['title' => '', 'data' => null, 'defaultContent' => '', 'class' => 'dt-details-control', 'searchable' => false, 'orderable' => false],
            'causer' => ['title' => trans('cortex/foundation::common.causer'), 'name' => 'causer.username', 'searchable' => false, 'orderable' => false, 'render' => 'full.causer_route ? "<a href=\""+full.causer_route+"\">"+data+"</a>" : data', 'responsivePriority' => 0],
            'description' => ['title' => trans('cortex/foundation::common.description'), 'orderable' => false],
            'created_at' => ['title' => trans('cortex/foundation::common.date'), 'render' => "moment(data).format('YYYY-MM-DD, hh:mm:ss A')"],
        ];
    }
}
