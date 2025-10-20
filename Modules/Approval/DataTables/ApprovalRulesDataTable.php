<?php
namespace Modules\Approval\DataTables;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class ApprovalRulesDataTable extends DataTable
{
    public function dataTable($query){
        return datatables()->query($query)
        ->editColumn('is_active', function($data) {
            if ($data->is_active) {
                return '<span class="badge badge-success">Aktif</span>';
            }
            return '<span class="badge badge-danger">Nonaktif</span>';
        })

        ->filterColumn('type_name', function($query, $keyword) {
            $query->where('approval_types.approval_name', 'like', "%{$keyword}%");
       })
        
        ->addColumn('action', fn($data) => view('approval::approval_rules.partials.actions', compact('data')))
        ->rawColumns(['action', 'is_active']);
    }

    public function query(){
        return DB::table('approval_rules')
            ->join('approval_types','approval_rules.approval_types_id','=','approval_types.id')
            ->select('approval_rules.id','approval_types.approval_name as type_name','approval_rules.rule_name','approval_rules.is_active','approval_rules.created_at');
    }

    public function html(){
        return $this->builder()
            ->setTableId('approval-rules-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom("<'row'<'col-md-3'l><'col-md-6 mb-2'B><'col-md-3'f>>tr<'row'<'col-md-5'i><'col-md-7'p>>")
            ->orderBy(1)
            ->buttons(
                Button::make('excel')->text('Excel'),
                Button::make('print')->text('Print'),
                Button::make('reset')->text('Reset'),
                Button::make('reload')->text('Reload')
            );
    }

    protected function getColumns(){
        return [
            Column::make('id')->title('#'),
            Column::make('type_name')
                  ->title('Type')
                  ->searchable(false), // <-- TAMBAHKAN INI
            Column::make('rule_name')->title('Rule'),
            Column::make('is_active')->title('Active'),
            Column::computed('action')->exportable(false)->printable(false)->addClass('text-center')
        ];
    }

    protected function filename(): string { return 'approval_rules_'.date('YmdHis'); }
}
