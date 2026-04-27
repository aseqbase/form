<?php
use MiMFa\Library\Convert;
use MiMFa\Library\Struct;
use MiMFa\Module\Table;
$data = $data??[];
$routeHandler = function ($data) {
    module("Table");
    $module = new Table(table("Form"));
    $module->KeyColumns = ["Name", "Title"];
    $module->IncludeColumns = ["Id", "Name", "Image", "Title", "Status", "Inbox" => "Id", "Description", "UpdateTime"];
    $module->ExcludeColumns = ["Id"];
    $module->SelectCondition = "ORDER BY UpdateTime DESC";
    $module->PrependControls = fn($v, $row) => [
        Struct::Icon("inbox", "/administrator/form/inbox?Id=$v", ["tooltip" => "To see the 'inbox'"]),
        Struct::Icon("eye"/*"arrow-up-right-from-square" */ , "/form/{$row["Name"]}", ["tooltip" => "To see the 'form'"]),
        Struct::Icon("list-alt", "/administrator/form/fields?Id=$v", ["tooltip" => "To manage the 'fields'"])
    ];
    $module->RemoveRequestHandler = fn($id) =>
        \_::$Back->DataBase->if()->Table("Form_Field")->Delete("FormId=:Id", [":Id" => $id])
        ->then()->Table("Form_Inbox")->Delete("FormId=:Id", [":Id" => $id])?null:null;
    $module->Controlable =
        $module->Updatable = true;
    $module->ViewAccess = false;
    $module->UpdateAccess = \_::$User->AdminAccess;
    $module->CellsValues = [
        "Name" => fn($v, $k, $r) => Struct::Link("\${{$v}}", "/administrator/form/fields?Id={$r["Id"]}"),
        "Title" => fn($v, $k, $r) => Struct::Link($v, "/form/{$r["Name"]}", ["target" => "blank"]),
        "Status" => fn($v) => Struct::Span($v > 0 ? "Published" : ($v < 0 ? "Unpublished" : "Drafted")),
        "Inbox" => fn($v) => Struct::Span(table("Form_Inbox")->Count("Id", "FormId=:Id", [":Id" => $v]), "/administrator/forms/inbox?Id=$v"),
        "CreateTime" => fn($v) => Convert::ToShownDateTimeString($v),
        "UpdateTime" => fn($v) => Convert::ToShownDateTimeString($v)
    ];
    $module->CellsTypes = [
        "Id" => "number",
        "Name" => "text",
        "Title" => "text",
        "Image" => "image",
        "Description" => "texts",
        "Email" => "email",
        "Method" => ["GET" => "GET", "POST" => "POST"],
        "Action" => "url",
        "Target" => "url",
        "Template" => [
            "d" => "Default",
            'v' => "Vertical",
            'h' => "Horizontal",
            'b' => "Both",
            't' => "Table",
            's' => "Special",
        ],
        "Content" => "content",
        "Status" => function ($t, $v) {
            $std = new stdClass();
            $std->Type = "Select";
            $std->Value = isEmpty($v) ? 1 : $v;
            $std->Options = [-1 => "Unpublished", 0 => "Drafted", 1 => "Published"];
            return $std;
        },
        "Access" => function () {
            $std = new stdClass();
            $std->Type = "number";
            $std->Attributes = ["min" => \_::$User->BanAccess, "max" => \_::$User->SuperAccess];
            return $std;
        },
        "Attributes" => "json",
        "Style" => "CSS",
        "Script" => "JS",
        "UpdateTime" => function ($t, $v) {
            $std = new stdClass();
            $std->Type = \_::$User->HasAccess(\_::$User->SuperAccess) ? "calendar" : "hidden";
            $std->Value = Convert::ToDateTimeString();
            return $std;
        },
        "CreateTime" => function ($t, $v) {
            return \_::$User->HasAccess(\_::$User->SuperAccess) ? "calendar" : (isValid($v) ? "hidden" : false);
        },
        "MetaData" => "json"
    ];
    pod($module, $data);
    return $module->ToString();
};

(new Router())->if(\_::$User->HasAccess(\_::$User->AdminAccess))
    ->Get(function () use ($routeHandler) {
        (\_::$Front->AdminView)($routeHandler, [
            "Image" => "check-square",
            "Title" => "'Forms' 'Management'"
        ]);
    })
    ->Default(fn() => response($routeHandler($data)))
    ->Handle();