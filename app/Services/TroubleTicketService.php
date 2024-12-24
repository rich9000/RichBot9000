<?php

namespace App\Services;




// this class must be defined before we start a session or SOAP Server:
use App\Console\Commands\ParseTickets;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class TroubleTicketService {

     public static function createFromJson($request)
    {

        Log::info('create ticket from this request::');
        Log::info($request);

        // Create the ticket entry
        $ticket = Ticket::where('file_name',$request['file_name'])->first();

        if(!$ticket){

            Log::info("NO TICKET FOUND. {$request['file_name']}");
            //$ticket = new Ticket($request);
            //$ticket->save();

            return false;

        }

        // Define validation rules
        $rules = [
            //'raw_data' => 'required|string',
            'summary_text' => 'required|string',
            'file_name' => 'required|string',
            'order_number' => 'nullable|string',
            'start_date' => 'nullable|date',
            'complete_date' => 'nullable|date',
            'status' => 'nullable|string',
            'account_number' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'service_address' => 'nullable|string',
            'contact_number' => 'nullable|string',
            'email' => 'nullable|string|email',
            'product_type' => 'nullable|string',
            'service_type' => 'nullable|string',
            'connect_date' => 'nullable|date',
            'disconnect_date' => 'nullable|date',
            'equipment' => 'nullable|string',
            'technician_name' => 'nullable|string',
            'install_notes' => 'nullable|string',
            'drop_type' => 'nullable|string',
            'issues_reported' => 'nullable|string',
            'resolution_notes' => 'nullable|string',
            'billing_amount' => 'nullable|numeric',
            'promotions' => 'nullable|string',
            'monthly_charge' => 'nullable|numeric',
            'fractional_charge' => 'nullable|numeric',
            'prorated_charge' => 'nullable|numeric',
            'warnings' => 'nullable|string',
            'comments' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'ssid' => 'nullable|string',
            'password' => 'nullable|string'
        ];

        // Validate incoming request data
        $validator = Validator::make($request, $rules);

        if($validator->failed()){

         Log::info('Validator Failed');
         return $validator->errors();

        }


        // Date fields to process
        $dateFields = ['start_date', 'complete_date', 'connect_date', 'disconnect_date'];

        // Process and format date fields
        $data = $request;
        foreach ($dateFields as $field) {
            if ($data[$field] == 'N/A' || $data[$field] == 'In Progress') {

                unset($data[$field]);
                continue;
            }

            if(isset($data[$field]) && !$data[$field]){

                unset($data[$field]);
                continue;
            }

            if (!empty($data[$field])  && $data[$field] != 'N/A' && $data[$field] != 'In Progress') {

                Log::info('date field not empty.'."$field ". $data[$field]);

                $data[$field] = Carbon::parse($data[$field])->format('Y-m-d') ?? null;

                Log::info('New Date:.'."$field ". $data[$field]);


            }

        }

        if($ticket){


            Log::info($data);


            return $ticket->update($data);

        }




        // Handle validation failure
        if ($validator->fails()) {
            Log::info($validator->errors()->toJson());
            return ['errors' => $validator->errors()];
        }

        // Create the ticket entry
        $ticket = Ticket::updateOrCreate(['file_name'=>$data['file_name']],$request );



        Log::info('Created Ticket:');
        Log::info($ticket);

       // exit;
        // Return success response
        return $ticket;
    }

    public static function getNewTicket()
    {

        return Ticket::where('order_number',null)->first();

    }







}
