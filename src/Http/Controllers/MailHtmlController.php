<?php

namespace Isotopes\Profiler\Http\Controllers;

use Illuminate\Routing\Controller;
use Isotopes\Profiler\Contracts\EntriesRepository;

class MailHtmlController extends Controller
{
    /**
     * Get the HTML content of the given email.
     *
     * @param EntriesRepository $storage
     * @param int $id
     * @return mixed
     */
    public function show(EntriesRepository $storage, $id)
    {
        return $storage->find($id)->content['html'];
    }
}
