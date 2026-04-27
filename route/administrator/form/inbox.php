<?php
use MiMFa\Library\Convert;
use MiMFa\Library\Struct;
use MiMFa\Module\Table;
$data = $data??[];
$routeHandler = function ($data) {
    $formId = get($data, "Form")??receiveGet("Id");
    module("Table");
    $module = new Table(table("Form_Inbox"));
    $module->KeyColumns = ["User", "Overview"];
    $module->IncludeColumns = ["Id", ...($formId?[]:["Form" => "FormId"]), "User" => "UserId", "Overview" => "Data", "UpdateTime", "CreateTime"];
    $module->ExcludeColumns = ["Id"];
    if ($formId) {
        $module->SelectCondition = "FormId=:Id";
        $module->SelectParameters = [":Id" => $formId];
    }
    $module->SelectCondition .= " ORDER BY UpdateTime DESC";
    $module->AllowDataTranslation = false;
    $module->Controlable =
        $module->Updatable = true;
    $module->UpdateAccess = \_::$User->AdminAccess;
    $forms = table("Form")->SelectPairs("Id", "Name");
    $users = table("User")->SelectPairs("Id", "Name");
    $module->CellsValues = [
        "Form" => fn($v) => Struct::Span($forms[$v] ?? Struct::Icon('list-alt'), "/administrator/form/fields?Id=$v"),
        "User" => fn($v) => Struct::Span("\${".($users[$v] ?? ""). "}", \_::$Address->UserRootUrlPath . $v),
        "Overview" => function($v) {
            $v = Convert::FromJson($v);
            if(count($v)>2) return Convert::ToText(first($v))." ".Convert::ToText(last($v));
            else return Struct::Convert($v);
        },
        "CreateTime" => fn($v) => Convert::ToShownDateTimeString($v),
        "UpdateTime" => fn($v) => Convert::ToShownDateTimeString($v)
    ];
    $module->CellsTypes = [
        "Id" => "number",
        "FormId" => function ($t, $v) use ($formId) {
            $std = new stdClass();
            $std->Title = "Form";
            $std->Value = $v ?: $formId;
            if ($formId)
                $std->Type = "Hidden";
            else {
                $std->Type = "Select";
                $std->Options = table("Form")->SelectPairs();
            }
            return $std;
        },
        "UserId" => function ($t, $v) use ($users) {
            $std = new stdClass();
            $std->Title = "User";
            $std->Type = \_::$User->HasAccess(\_::$User->SuperAccess) ? "select" : "hidden";
            $std->Options = $users;
            if (!isValid($v))
                $std->Value = \_::$User->Id;
            return $std;
        },
        "Data" => "object",
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
        $id = received("Id");
        $row = $id ? table("Form")->Get($id) : null;
        (\_::$Front->AdminView)($routeHandler, [
            "Form" => $id,
            "Path" => "/form/$id",
            "Image" => ($row["Image"] ?? null) ?: "inbox",
            "Title" => "'" . ($name = (($row["Title"] ?? null) ?: "Form")) . "' 'Inbox'",
            "Description" => ($row["Description"] ?? null) ?: ($id?"'Edit' '$name' 'Inbox'":null)
        ]);
    })
    ->Default(fn() => response($routeHandler($data)))
    ->Handle();