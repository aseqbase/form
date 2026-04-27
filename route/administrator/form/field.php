<?php
use MiMFa\Library\Convert;
use MiMFa\Library\Struct;
use MiMFa\Module\Table;
$data = $data??[];
$routeHandler = function ($data) {
    $formId = get($data, "Form")??receiveGet("Id");
    $fieldKey = get($data, "Field")??receiveGet("Key");
    if(!$fieldKey) return deliverError("There is not select any field!");
    $fieldKey = table("Form_Field")->GetValue($fieldKey, "Name");
    if(!$fieldKey) return deliverError("Could not find the field!");
    module("Table");
    $module = new Table(table("Form_Inbox"));
    $module->KeyColumns = ["Value"];
    $module->IncludeColumns = ["Id", "User" => "UserId", "Value" => "Data", "UpdateTime", "CreateTime"];
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
    $users = table("User")->SelectPairs("Id", "Name");
    $module->CellsValues = [
        "User" => fn($v) => Struct::Span("\${".($users[$v] ?? ""). "}", \_::$Address->UserRootUrlPath . $v),
        "Value" => fn($v) => Struct::Convert(Convert::FromJson($v)[$fieldKey]??null),
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
        $key = received("Key");
        if(!$key) return null;
        $row = $key ? table("Form_Field")->Get($key) : null;
        (\_::$Front->AdminView)($routeHandler, [
            "Form" => $id,
            "Field" => $key,
            "Path" => "/form/fields?id=$id",
            "Title" => "'" . ($name = (get($row, "Name") ?: "Field")) . "' 'Values'",
            "Description" => Struct::Big(get($row, "Title") ?? Convert::ToTitle($name)).Struct::$BreakLine.Struct::Paragraph(get($row, "Description") ?: "'See' all '$name' 'Values'")
        ]);
    })
    ->Default(fn() => response($routeHandler($data)))
    ->Handle();