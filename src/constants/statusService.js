export class ServiceStatus
{
    static sendService = 0;
    static returnService = 1;
}

(function () {
    window.ServiceStatus = ServiceStatus;
})();
