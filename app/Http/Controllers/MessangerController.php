<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache as Cache;


use Telegram\Bot\Api;
use App\Models\TelegramUsers;
use App\Models\TelegramGroup;
use App\Models\TelegramGroupSubscription;
use App\Models\TelegramUserSubscriptionRequest;
use App\Models\TelegramMessage;

/**
 * Class MessangerController.
 */
class MessangerController_old extends Controller
{
    protected $telegram;

    /**
          * @OA\Post(
          * operationId="constructor",
          * tags={"constructor"},
          * summary="Just a basic constructor.",
          * description="Just a basic constructor.",
          * @OA\Property(property="telegram", type="instance"),
          *    
          * )
          */
    public function __construct(Api $telegram)
    {
        $this->telegram =  new Api(env("TELEGRAM_BOT_TOKEN"));
    }


    /**
          * @OA\Post(
          * path="/api/v1/setwebhook",
          * operationId="setwebhook",
          * tags={"setwebhook"},
          * summary="This is used to set the webhook url",
          * description="According to Telegram API documentation, a webhook url must be registed using the {setWebhook} method, as such this route takse advantage of the previously mentioned method to register a webhook route on telegram server. ",
          *      @OA\Response(
          *          response=200, 
          *       ),
          *    
          * )
          */

    public function setWebhook()
    {
        try {
            $url = env('webhook_URL');
            $setWebhook = $this->telegram->setWebhook([
                'url' => $url,
            ]);
            if ($setWebhook){
                return response()->json(['status'=>'success', 'message'=>'something went wrong .',"data" =>$setWebhook]);
            }else{
                return response()->json(['status'=>'error', 'message'=>'something went wrong .']);
            }

        } catch (Exception $e) {
            $error = $e->getMessage().', '.$e->getFile().', '.$e->getLine();
            return response()->json(['status'=>'error', 'message'=>'something went wrong .',"data" =>$error]);
            
        }
    }

     /**
          * @OA\Post(
          * path="/api/v1/webhook",
          * operationId="webhook",
          * tags={"webhook"},
          * summary="this is a webhook to be used to recieve instant update from telegram",
          * description="this is a webhook to be used to recieve instant update from telegram.",
          * @OA\Property(property="request", type="object"),
          *      @OA\Response(
          *          response=200, 
          *       ),
          *    
          * )
          */
    public function webhook(Request $request):void
    {
        
        $telegramRequest = $request->all();
        $user = new TelegramUsers();
        $groupModel = new TelegramGroup();
        $subscription = new TelegramGroupSubscription();
        $requestSubscription = new TelegramUserSubscriptionRequest();
        


        if (!isset($telegramRequest['message']['chat']['id']) || !in_array($telegramRequest['message']['chat']['type'], ['group', 'supergroup'])){
            exit();
        }
            

        $groupID       = $telegramRequest['message']['chat']['id'];
        $isBot         = $telegramRequest['message']['from']['is_bot'];
        $from          = $telegramRequest['message']['from'];
        $fromID        = $from['id'];
        $fromFirstName = isset($from['first_name']) ? $from['first_name'] : null;
        $fromLastName  = isset($from['last_name']) ? $from['last_name'] : null;
        $fromUserName  = isset($from['username']) ? $from['username'] : null;
        $fromName      = $fromFirstName.' '.$fromLastName;
        $fromName      = trim(str_replace(['(', ')', '[', ']'], '', $fromName));
        $chatType      = $telegramRequest['message']['chat']['type'];
        $isNewChatMember = isset($telegramRequest['message']['new_chat_member']);
        $text            = isset($telegramRequest['message']['text']) ? trim($telegramRequest['message']['text']) : false;
        $messageID  = isset($telegramRequest['message']['message_id']) ? trim($telegramRequest['message']['message_id']) : false;

        // do nothing if its a bot,
        // todo : if its a bot log the message to be a king of history for the full communication, for now the system does nothing.
        // we would have to check that the bot is our bot to even take action in the first instance.
        if ($isBot){
            exit();
        }
        if (empty($fromName)){
            $fromName = "no name";
        }

        if ($isNewChatMember) {
            $newMember = $telegramRequest['message']['new_chat_member'];
            $user = TelegramUsers::where(["user_id" => $newMember["id"]])->first();
            // check if user exists
            if(!$user){
                // create a new user
                $user = new TelegramUsers();
                $user->user_id = $newMember["id"];
                $user->first_name = isset($newMember['first_name']) ? $newMember['first_name'] : null;
                $user->last_name = isset($newMember['last_name']) ? $newMember['last_name'] : null;
                $user->user_name =  isset($newMember['username']) ? $newMember['username'] : null;
                if($user->save()){
                    
                $getGroup = $groupModel->where(["group_id" => $groupID ])->first();
                // check if goup already exist
                if(!$getGroup){
                    // create new group
                    $groupModel->group_id = $groupID;
                    $groupModel->group_type = $chatType; 
                
                    if($groupModel->save()){
                        $dbGroupID = $groupModel->id;
                        // make a new subscription request 
                        $requestSubscription->group_id = $dbGroupID;
                        $requestSubscription->user_id = $user->id;
                        $requestSubscription->message = $text;
                        $requestSubscription->telegram_message_id = $messageID;
                        $requestSubscription->approval_status = $requestSubscription->pending;

                        
                        if($requestSubscription->save()){
                            exit();
                        } 

                    }
                }else{
                    
                    // this area means that the goup already exist in our database.
                    $dbGroupID = $getGroup->id;
                    // make a new subscription request 
                    $requestSubscription->group_id = $dbGroupID;
                    $requestSubscription->user_id = $user->id;
                    $requestSubscription->message = $text;
                    $requestSubscription->telegram_message_id = $messageID;
                    $requestSubscription->approval_status = $requestSubscription->pending;

                    
                    if($requestSubscription->save()){
                        exit();
                    } 
                }
    
                }
            }
        }
            
     
        $user = TelegramUsers::where(["user_id" => $fromID])->first();
        // check if user exist
        if(!$user){
            // create a new user
            $user = new TelegramUsers();
            $user->user_id = $fromID;
            $user->first_name = $fromFirstName;
            $user->last_name = $fromLastName;
            $user->user_name =  $fromUserName;
            if($user->save()){
                // check the chat type and if its private , then add a new private group else search if group already exist.
                if($chatType == "private"){
                    // create group
                    $groupModel->group_id = $groupID;
                    $groupModel->group_type = $chatType; 
                    
                    if($groupModel->save()){
                        $dbGroupID = $groupModel->id;
                        // make a new subscription request 
                        $requestSubscription->group_id = $dbGroupID;
                        $requestSubscription->user_id = $user->id;
                        $requestSubscription->barn_status = $requestSubscription->pending;
                        
                        if($requestSubscription->save()){
                            exit();
                        } 

                    }
                    
                }else{
                    $getGroup = $groupModel->where(["group_id" => $groupID ])->first();
                    if(!$getGroup){
                        // create new group
                        $groupModel->group_id = $groupID;
                        $groupModel->group_type = $chatType; 
                    
                        if($groupModel->save()){
                            $dbGroupID = $groupModel->id;
                            // make a new subscription request 
                            $requestSubscription->group_id = $dbGroupID;
                            $requestSubscription->user_id = $user->id;
                            $requestSubscription->message = $text;
                            $requestSubscription->telegram_message_id = $messageID;
                            $requestSubscription->approval_status = $requestSubscription->pending;

                            
                            if($requestSubscription->save()){
                                exit();
                            } 

                        }
                    }else{
                        
                        $dbGroupID = $getGroup->id;
                        // make a new subscription request 
                        $requestSubscription->group_id = $dbGroupID;
                        $requestSubscription->user_id = $user->id;
                        $requestSubscription->message = $text;
                        $requestSubscription->telegram_message_id = $messageID;
                        $requestSubscription->approval_status = $requestSubscription->pending;

                        
                        if($requestSubscription->save()){
                            exit();
                        } 
                    }
                }

            }
        }

        /**
         * This section would check if a message is from a subscribed user.
         *  if message is from a subscribed user  log the message inside of the message table to be attended to by an admin member.
         *  if the user is not subscribed , do nothing.
         *  if the user subscription is pending, do nothing.
         *  if the user is barnned from a group use the telegram api to barn the user instantly so such user would not be able to send a message to the group again .
         * */ 
        
       if(!$this->getSubscription($fromID,$groupID)){
            // barnd
            exit();
        }else{
            // insert messsage.
            $messageID = $toMessageID = $telegramRequest['message']['message_id'];
            $isReplyID = isset($telegramRequest['message']['reply_to_message']) ? $telegramRequest['message']['reply_to_message']['message_id'] : false;

            if ($isReplyID && $text) {      
                $replyFromID = $telegramRequest['message']['reply_to_message']['from']['id'];
            
                $isBot = $telegramRequest['message']['reply_to_message']['from']['is_bot'];
                if ($isBot){
                    exit();
                }else{
                    // insert message to message table 
                    
                    saveMessage($groupID,$fromID,$text,$messageID,$isReplyID);
                    exit();
                }

                
                    
            } else{
                // insert message to message table
                saveMessage($groupID,$fromID,$text,$messageID);
                exit();
            }
        }
      
    }

    private function getSubscription($userId,$groupId){
        // use the user id and group id to fetch the subscription
        // first check for user details
        // check for group details
        // then search for user subscription
        // if subscription does not exist then search the request to know the status of the request

        // get user
        $user = TelegramUsers::where(["user_id" => $userId])->first();
        // get group.
        $group = TelegramGroup::where(["group_id" => $groupId])->first();
        // get subscription 
        $getSubscriptionStatus = TelegramGroupSubscription::where(["group_id" => $group->id,"user_id" => $user->id])->first();

        // if there is a valid subscription check the status.
        if($getSubscriptionStatus){
            if($getSubscriptionStatus->barn_status == TelegramUserSubscriptionRequest::$approved){
                // if the user has right to post to group
                return true;
            }

            if($getSubscriptionStatus->barn_status == TelegramUserSubscriptionRequest::$banned){
                // if the user is barnned
                return "false";
            }

        }else{
            // check if there is a request and what the request status is 
            
            // this is a todo task.
        }

    }

    private function saveMessage($groupId,$userId,$text,$messageId,$replyId = null){
        // get user id
        // get group id
        // save message
        
         // get user
         $user = TelegramUsers::where(["user_id" => $userId])->first();
         // get group.
         $group = TelegramGroup::where(["group_id" => $groupId])->first();

         $message = new TelegramMessage();

        if($replyId == null){
            // regular message
            $message->group_id = $groupId;
            $message->user_id = $userId;
            $message->message = $text;
            $message->telegram_message_id = $messageId;
            if($message->save()){
                return true;
            }

            return false;
           
        }else{
            // reply message
            $message->group_id = $groupId;
            $message->user_id = $userId;
            $message->message = $text;
            $message->telegram_message_id = $messageId;
            $message->telegram_reply_id = $replyId;
            if($message->save()){
                return true;
            }

            return false;
        }

    }

     /**
          * @OA\Get(
          * path="/api/v1/subscription",
          * operationId="subscription",
          * tags={"subscription"},
          * summary="Helps admin to approve or decline a users from sending message or from being a part of the group.",
          * description="Helps admin to approve or decline a users from sending message or from being a part of the group.",
          * @OA\Property(property="id", type="integer"),
          *  @OA\Property(property="status", type="integer"),
          *
          *      @OA\Response(
          *          response=200, 
          *       ),
          *    
          * )
          */
    public function subscriptionStatus($id,$status){
        // this function is responsible for approving or declining a subscription
        // if subscription is approved then create a new record in the subscription table
        // if subscription is declined , then create a new record in the subscription table and also barn the user on telegram
        // get info from subscription request and approve subscription
        $requestid = $id;
        $status = $status;
        $request = TelegramUserSubscriptionRequest::where(['id' => $requestid])->first();
        $subscription = new TelegramGroupSubscription();
        $getGroup = TelegramGroup::where(["id" => $request->group_id ])->first();
        $getUser = TelegramUsers::where(["id" => $request->user_id ])->first();
        $telegaramGroupId = $getGroup->group_id;
        $telegramUserId = $getUser->user_id;
        if($status == TelegramUserSubscriptionRequest::$approved){
            // this means the user was approved 
            $subscription->group_id =$request->group_id;
            $subscription->user_id = $request->user_id;
            $subscription->barn_status = TelegramUserSubscriptionRequest::$approved;
            if($subscription->save()){
                $request->delete();
                return response()->json(["status"=>"success","data" => $subscription],200);
            }else{
                return response()->json(["status"=>"error","message" => "could not approve subscription at this time, please try again later."],400);
            }
            
        }

        if($status == TelegramUserSubscriptionRequest::$banned){
            // this means the user was banned 
            $subscription->group_id =$request->group_id;
            $subscription->user_id = $request->user_id;
            $subscription->barn_status = TelegramUserSubscriptionRequest::$banned;
            if($subscription->save()){
                $request->delete();
                // kick user out from telegram group using the group and user id
                $groupId = $telegaramGroupId;
                $userId = $telegramUserId;
                $remove = $this->telegram->kickChatMember([
                    'chat_id' => $groupId,
                    'user_id' => $userId
                ]);
                if($remove){
                    return response()->json(["status"=>"success","data" => $subscription],200);
                }
                return response()->json(["status"=>"error","data" => "we were unable to bann the user from telegram server"],400);
            }

            return response()->json(["status"=>"error","message" => "could not ban user from the group at this time at this time, please try again later."],400);
            
        }

    }

    /**
          * @OA\Post(
          * path="/api/v1/sendmessage",
          * operationId="sendmessage",
          * tags={"sendmessage"},
          * summary="Send a mesage to a telegram user.",
          * description="To be able to send a message to a tellegram user and also to be able to also send a reply to a previously sent message. ",
          * @OA\Property(property="id", type="integer"),
          *  @OA\Property(property="request", type="object"),
          *
          *
          *   @OA\Parameter(
          *      name="text",
          *      in="query",
          *      required=true,
          *      @OA\Schema(
          *           type="string"
          *      )
          *   ),
          *       * @OA\Parameter(
          *      name="message_id",
          *      in="query",
          *      required=false,
          *      @OA\Schema(
          *           type="integer"
          *      )
          *   ),
          *      @OA\Response(
          *          response=200,
          *          description="message was sent.",
          *          @OA\JsonContent()
          *       ),
          *    
          *      @OA\Response(response=400, description="message was not sent."),
          *    
          * )
          */
    public function sendMessage($id,Request $request){
        // we have two types of basic messagess that could be sent
        //1 . A fresh message
        //2 . A reply to a fresh message.

        $getId = TelegramGroup::where(["id" => $id])->first();
        if(isset($request->message_id)){
            // send reply to a previous message 
            $sendMessage = $this->telegram->sendMessage([
                'chat_id' => $getId->group_id,
                'reply_to_message_id' => $request->message_id,
                'text' => $request->text,
            ])->getRawResponse();

            if($sendMessage){
                return response()->json(["status" => "success", "message" => "message was sent",'data' => $sendMessage],200);
            }
            return response()->json(["status" => "error", "message" => "message was not sent", "data" => $sendMessage],400);
        }
        
        $sendMessage =  $this->telegram->sendMessage([
            'chat_id' => $getId->group_id,
            'text' => $request->text,
        ])->getRawResponse();

        if($sendMessage){
            return response()->json(["status" => "success", "message" => "message was sent",'data' => $sendMessage],200);
        }
        return response()->json(["status" => "error", "message" => "message was not sent", "data" => $sendMessage],400);
    }
}