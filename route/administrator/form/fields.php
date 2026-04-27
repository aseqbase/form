<?php
use MiMFa\Library\Convert;
use MiMFa\Library\Struct;
use MiMFa\Module\Table;
$data = $data??[];
$routeHandler = function ($data) {
    $types = [
        'Span' => "SPAN: Inline text display (&lt;span&gt;), non-editable label.",
        'Division' => "DIVISION: Block container (&lt;div&gt;) for arbitrary content.",
        'Paragraph' => "PARAGRAPH: Paragraph (&lt;p&gt;) block of text.",
        'Label' => "LABEL: Form label tied to an input (for attribute).",
        'Break' => "BREAK: To go on the new line (&lt;br&gt;).",
        'BreakLine' => "BREAKLINE: A horizontal row (&lt;hr&gt;).",
        'Progress' => "PROGRESS: A progress bar (&lt;progress&gt;).",
        'Disabled' => "DISABLED: Read-only / disabled input presentation.",
        'Input' => "INPUT: Generic single-line input (text by default).",
        'Pairs' => "PAIRS: A Key-Value pair Collection repeated inputs.",
        'Collection' => "COLLECTION: Collection of items repeated inputs.",
        'Text' => "TEXT: Single-line text input.",
        'Texts' => "TEXTS: Multi-line textarea input.",
        'Content' => "CONTENT: Rich content editor (textarea + live preview).",
        'Script' => "SCRIPT: Code/script editor textarea (JS/HTML/CSS).",
        'Object' => "OBJECT: JSON/object editor (formatted textarea).",
        'Search' => "SEARCH: Search input (type=search).",
        'Find' => "FIND: Find/combo input with visible text + hidden value (datalist).",
        'Color' => "COLOR: Color picker input (type=color).",
        'Dropdown' => "DROPDOWN: Single-select dropdown (&lt;select&gt;).",
        'Dropdowns' => "DROPDOWNS: Multi-select dropdown (&lt;select multiple&gt;).",
        'Radio' => "RADIO: Single radio input.",
        'Radios' => "RADIOS: Group of radio buttons (multiple choices).",
        'Switch' => "SWITCH: Boolean toggle (visual switch).",
        'Switches' => "SWITCHES: Multiple boolean toggles.",
        'Check' => "CHECK: Single checkbox input.",
        'Checks' => "CHECKS: Multiple checkboxes collection.",
        'Integer' => "INTEGER: Integer number input (min/max supported).",
        'Short' => "SHORT: Small-range integer input (bounded short int).",
        'Long' => "LONG: Numeric input for larger integer values.",
        'Range' => "RANGE: Slider input with min/max (range).",
        'Code' => "CODE: Numeric code input (digits, optional fixed length).",
        'Symbolic' => "SYMBOLIC: Symbolic selector (visual symbols as choices).",
        'Float' => "FLOAT: Floating-point number input with precision/step.",
        'Tel' => "TEL: Telephone input (type=tel).",
        'Mask' => "MASK: Text input validated by regex pattern / mask.",
        'Url' => "URL: URL input (validated, accepts absolute or root paths).",
        'Map' => "MAP: Map picker (interactive Leaflet map) producing lat,lng.",
        'Path' => "PATH: File-or-path input (text fallback + file chooser).",
        'Address' => "ADDRESS: Multi-line address textarea.",
        'Calendar' => "CALENDAR: Calendar widget with date/time selection + hidden value.",
        'Datetime' => "DATETIME: Datetime-local input control.",
        'Date' => "DATE: Date-only input control.",
        'Time' => "TIME: Time-only input control.",
        'Week' => "WEEK: Week input control.",
        'Month' => "MONTH: Month input control.",
        'Hidden' => "HIDDEN: Hidden input field (type=hidden).",
        'Secret' => "SECRET: Password input (type=password).",
        'Document' => "DOCUMENT: Single document file uploader (document formats).",
        'Documents' => "DOCUMENTS: Multiple document uploader.",
        'Image' => "IMAGE: Single image file uploader (image formats).",
        'Images' => "IMAGES: Multiple image uploader.",
        'Audio' => "AUDIO: Single audio file uploader.",
        'Audios' => "AUDIOS: Multiple audio uploader.",
        'Video' => "VIDEO: Single video file uploader.",
        'Videos' => "VIDEOS: Multiple video uploader.",
        'File' => "FILE: Generic single file uploader.",
        'Files' => "FILES: Multiple file uploader.",
        'Directory' => "DIRECTORY: Directory selector (webkitdirectory / multiple).",
        'Email' => "EMAIL: Email input (type=email), validated format.",
        'Link' => "LINK: Anchor (&lt;a&gt;) that navigates to value or url.",
        'Action' => "ACTION: Clickable element that triggers JS action or load a path.",
        'Icon' => "ICON: Clickable icon that triggers JS action or load a path.",
        'Button' => "BUTTON: Clickable button that triggers JS action or load a path.",
        'Reset' => "RESET: Form reset button.",
        'Submit' => "SUBMIT: Form submit button.",
    ];
    $formId = get($data, "Form")??receiveGet("Id");
    module("Table");
    $module = new Table(table("Form_Field"));
    $module->KeyColumns = ["Name", "Title"];
    $module->IncludeColumns = ["Id", ...($formId?[]:["Form" => "FormId"]), "Name", "Required", "Type", "Title", "Status", "Description", "Priority", "CreateTime"];
    $module->ExcludeColumns = ["Id", "Required"];
    if ($formId) {
        $module->SelectCondition = "FormId=:Id";
        $module->SelectParameters = [":Id" => $formId];
    }
    $module->SelectCondition .= " ORDER BY Priority DESC";
    $module->Controlable =
        $module->Updatable = true;
    $module->UpdateAccess = \_::$User->AdminAccess;
    $module->PrependControls = fn($v, $row) => [
        Struct::Icon("filter", "/administrator/form/field?Id=$formId&Key=$v", ["tooltip" => "To see the 'values'"])
    ];
    $forms = table("Form")->SelectPairs("Id", "Name");
    $module->CellsValues = [
        "Form" => fn($v) => Struct::Span($forms[$v] ?? Struct::Icon('list-alt'), "/administrator/form/fields?Id=$v"),
        "Name" => fn($v, $k, $r) => Struct::Link("\${{$v}}", "/administrator/form/field?Id=$formId&Key=".$r["Id"]) . ($r["Required"] ? " " . Struct::Span("*", ["class" => "be fore red"]) : ""),
        "Status" => fn($v) => Struct::Span($v > 0 ? "Published" : ($v < 0 ? "Unpublished" : "Drafted")),
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
        "Type" => function ($t, $v) use ($types) {
            $std = new stdClass();
            $std->Type = "Find";
            $std->Value = $v;
            $std->Options = $types;
            return $std;
        },
        "Name" => "text",
        "Title" => "text",
        "Value" => "text",
        "Description" => "texts",
        "Options" => "json",
        "Required" => "switch",
        "Priority" => "int",
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
            "Image" => ($row["Image"] ?? null) ?: "list-alt",
            "Title" => "'" . ($name = (($row["Title"] ?? null) ?: "Form")) . "' 'Fields'",
            "Description" => ($row["Description"] ?? null) ?: ($id?"'Edit' '$name' 'Fields'":null)
        ]);
    })
    ->Default(fn() => response($routeHandler($data)))
    ->Handle();