<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperReportProspect
 */
class ReportProspect extends Model
{
    use HasFactory;

    // TODO: This was not originally present in the application, added it for now to not throw code analysis errors. We will need to figure out whether this is needed or not.
}
