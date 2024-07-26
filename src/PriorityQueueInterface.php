<?php

Interface PriorityQueueInterface
{

    public function isEmpty(): bool;
    public function push($element, $priority): bool;
    public function pop();
    public function purge(): void;
    public function count(): int;
    public function contains($element): bool;
    public function change_priority($element, $new_priority): bool;

    //* La forma de realizar los métodos 'contains' y 'change_priority' depende de ti.
    //* Yo uso tres métodos, pero no puedo incluirlos en este contrato.
    //* private function getPosition($elemento);
    //* private function down_heap($elemento, $pos, $prioridad): void;
    //* private function up_heap($elemento, $pos, $prioridad): void;

    // How you perform the 'contains' and 'change_priority' methods is up to you.
    // I use three methods, but cannot include them into this contract.
    // private function getPosition($element);
    // private function down_heap($element, $pos, $priority): void;
    // private function up_heap($element, $pos, $priority): void;

}
?>