<?php

namespace App\Rules;

use App\Models\Shipping;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;


class UniqueName implements Rule, DataAwareRule
{
    protected $table;

    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];
    /**
     * The ID that should be ignored.
     *
     * @var mixed
     */
    protected $ignore;
    /**
     * The name of the ID column.
     *
     * @var string
     */
    protected $idColumn = 'id';
    /**
     * Client ID
     * @var integer
     */
    private $client_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($client_id)
    {
        $this->client_id = $client_id;
        //TODO: can be changed to use any required table name...
        // right now use hardcoded model's table
        $this->table = (new Shipping())->getTable();
    }

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function ignore($id, $idColumn = null)
    {
        $this->ignore = $id;
        $this->idColumn = $idColumn ?? 'id';

        return $this;
    }


    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $item = DB::table($this->table)->select();
        $item->where('client_id', $this->client_id)->where('name', $value);
        if (!empty($this->ignore)) {
            $item->where($this->idColumn, '!=', $this->ignore);
        }

        return ($item->count()) ? false : true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Please ensure the :attribute of your shipping method is unique!";
    }
}
