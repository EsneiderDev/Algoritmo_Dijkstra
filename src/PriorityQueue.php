<?php

require_once 'PriorityQueueInterface.php';


/**
 * * Esta cola de prioridad se basa en el concepto de «montón» (un «montón» es una pila ordenada) y se 
 * * implementa utilizando un árbol binario.
 * * Su primer elemento debe ser 1.
 * 
 * This priority queue is based on the 'heap' concept (a 'heap' is an ordered stack) and is
 * implemented using a binary tree.
 * It's first element should be 1.
 *
 * @author Oscar Pascual <oscar.pascual@gmail.com>
 */
class PriorityQueue implements PriorityQueueInterface
{

    // * La cola en sí.  Es una matriz bidimensional.
    // The queue itself.  It is a bi-dimensional array.
    protected $queue;

    // * El número de elementos dentro de la cola.
    // The number of elements inside the queue.
    protected $num_elements;

    // * Un hashmap para rastrear las posiciones de todos los elementos dentro de la cola (truco de rendimiento).
    // A hashmap to track the positions of all the elements inside the queue (performance trick).
    protected $hashmap;

    // * El algoritmo hash.  Por defecto, 'crc32b'.
    // The hash algorithm.  By default, 'crc32b'.
    protected $algorithm;


    /**
     * * Crear una nueva cola (un array) sin elementos en ella. 
     * * Crea el hashmap.
     * 
     * Create a new queue (an array) without elements in it.
     * Create the hashmap.
     */
    public function __construct($algorithm = 'crc32b')
    {
        $this->queue        = array(array());
        $this->num_elements = 0;
        $this->hashmap      = array();
        $this->algorithm    = $algorithm;
    }


    /**
     * * Devuelve un booleano que indica si la cola está vacía o no.
     * * (verdadero = vacía)
     * 
     * Returns a boolean indicating wether the queue is empty or not.
     * (true = empty)
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return $this->num_elements == 0;
    }


    /**
     * * Este método devuelve un booleano que indica si un elemento está presente o no en la cola de prioridad.
     * * Para mayor rapidez, se utiliza un hashmap.
     * 
     * This method returns a boolean indicating wether an element is present or not in the priority queue.
     * For speed, this is done using a hashmap.
     *
     * @param Mixed $element
     * @return boolean
     */
    public function contains($element): bool
    {
        return isset($this->hashmap[hash($this->algorithm, $element)]);
    }


    /**
     * * Empujar un elemento a la cola de prioridad.  El proceso es:
     * * 1. Obtener la siguiente posición libre (última)
     * * 2. Obtener posición padre
     * * 3. Insertar o intercambiar posiciones.
     * 
     * Push an element into the priority queue.  The process is:
     *   1. Get next free position (last)
     *   2. Get father's position
     *   3. Insert or swith positions.
     *
     * @param Mixed $element
     * @param Integer $priority
     * @return boolean
     */
    public function push($element, $priority): bool
    {
        //* ¿Por qué se trata de forma diferente el primer elemento?  Porque de lo contrario el primer elemento 
        //* de la cola tendría índice 0 (cero), y eso no es lo deseado ;-)
        //Why is the first element treated differently?  Because otherwise the first element
        //in the queue would have index 0 (zero), and that is not desired.  ;-)
        if ($this->isEmpty()) {
            $this->num_elements                              = 1;
            $this->queue[1]['element']                       = $element;
            $this->queue[1]['priority']                      = $priority;
            $this->queue[1]['timestamp']                     = time();
            $this->hashmap[hash($this->algorithm, $element)] = 1;
            return true;
        }

        $this->num_elements++;

        //* Los nuevos elementos se colocan en la última posición y luego se «suben».
        //New elements are placed in last position, and then "up-heaped".
        $this->up_heap($element, $this->num_elements, $priority);

        return true;
    }


    /**
     * * Obtener el primer elemento de la cola de prioridad.
     * * Para tener la cola de prioridad correcta, obtenemos el último elemento y lo insertamos en la primera posición.  Después lo «bajamos» a su
     * * posición correcta.
     *
     * Get the first element of the priority queue.
     * In order to have the correct priority queue, we get the last element and insert it in
     * the first position.  After that we "down-heap" it to it's correct position.
     *
     * @return Mixed
     */
    public function pop()
    {
        //What if we perform a pop on an empty queue?  Throw exception.
        if ($this->isEmpty()) {
            throw new EmptyQueueException("Queue is empty");
        }

        $first_element = $this->queue[1];
        $last_element  = $this->queue[$this->num_elements];

        $this->num_elements--;
        array_pop($this->queue);

        //It's also necessary to unset the element's position inside the hashmap.
        unset($this->hashmap[hash($this->algorithm, $first_element['element'])]);

        //The last element is placed on top of the heap, and then "down-heaped".
        $this->down_heap($last_element['element'], 1, $last_element['priority']);

        return $first_element['element'];
    }


    /**
     * * Cambiar la prioridad de un elemento.
     * * Esto se hace buscando el elemento en la cola, cambiar su prioridad, y moverlo hacia arriba o hacia abajo a su lugar correcto.
   
     * * Casos posibles:
     * *    a) es el primer elemento de la cola de prioridad y la nueva prioridad es mayor: ¡no hagas nada!
     * *    b) es el primer elemento de la cola de prioridad y la nueva prioridad es más baja: realiza un down-heap.
     * *    c) es el último elemento de la cola de prioridad y la nueva prioridad es mayor: realizar up-heap.
     * *    d) es el último elemento de la cola de prioridad y la nueva prioridad es menor: ¡no hacer nada!
     * *    e) es un elemento intermedio: comprueba qué operación realizar.
     
     * *  Importante: el coste de esta operación es O(log n) en el peor de los casos, gracias al hashmap.

     * Change one element's priority.
     * This is done by searching the element in the queue, change it's priority, and move it
     * up or down to it's correct place.
     *
     * Possible cases:
     *    a) it's the top element of the priority queue and new priority is higher: do nothing!
     *    b) it's the top element of the priority queue and new priority is lower: perform down-heap.
     *    c) it's the last element of the priority queue and new priority is higher: perform up-heap.
     *    d) it's the last element of the priority queue and new priority is lower: do nothing!
     *    e) it's an intermediate element: check what operation to perform.
     *
     * Important: cost for this operation is O(log n) in worst case, thanks to the hashmap.
     *
     * @return bool
     */
    public function change_priority($element, $new_priority): bool
    {
        // Obtiene la posición del elemento en la cola.  Debe estar presente o devolverá false.
        // Get the position of the element in the queue.  Must be present, or return false.
        $pos = $this->hashmap[hash($this->algorithm, $element)];

        if ($pos) {
            //* Es el primer elemento, y la nueva prioridad es menor que la actual
            //* El elemento debe tener prioridad baja
            //* En caso contrario... no hacer nada.
            // It's the first element, and the new priority is lower than current
            // Element must be down-heaped
            // Otherwise... do nothing.
            if (($pos == 1) && ($this->queue[$pos]['priority'] < $new_priority)) {
                $this->down_heap($element, $pos, $new_priority);
                return true;
            } else if (($pos == 1) && ($this->queue[$pos]['priority'] > $new_priority)) {
                return true;


            //* Es el último elemento, y la nueva prioridad es mayor que la actual
            //* El elemento debe tener prioridad superior
            //* En caso contrario... no hacer nada.
            // It's the last element, and the new priority is higher than current
            // Element must be up-heaped
            // Otherwise... do nothing.
            } else if (($pos == $this->num_elements) && ($this->queue[$pos]['priority'] > $new_priority)) {
                $this->up_heap($element, $pos, $new_priority);
                return true;
            } else if (($pos == $this->num_elements) && ($this->queue[$pos]['priority'] < $new_priority)) {
                return true;


            } else {
                //* El elemento está en algún lugar de la cola... comprobemos la prioridad del padre y decidamos.
                // Element is somewhere in the queue... let's check father's priority and decide.
                $fathers_position = intdiv($pos, 2);

                //* Y ahora, si la prioridad de padre es mayor, necesitamos subir el elemento.  En caso contrario,
                //* realizamos un down-heap sin necesidad de comprobar la prioridad del hijo.
                // And now, if father's priority is higher, we need to up-heap the element.  If not,
                // we perform a down-heap without the need to check child's priority.
                if ($new_priority < $this->queue[$fathers_position]['priority']) {
                    $this->up_heap($element, $pos, $new_priority);
                } else {
                    $this->down_heap($element, $pos, $new_priority);
                }

                return true;
            }

        } else {
            return false;
        }
    }


    /**
     * * Eliminar todos los elementos de la cola, poner num_elements a cero y vaciar el hashmap.
     * Eliminate all the queue elements, set num_elements to zero and empty the hashmap.
     *
     * @return void
     */
    public function purge(): void
    {
        array_splice($this->queue, 0);
        $this->num_elements = 0;

        array_splice($this->hashmap, 0);
    }


    /**
     * * Devuelve el número de elementos de la cola
     * Returns the number of elements in the queue
     *
     * @return integer
     */
    public function count(): int
    {
        return $this->num_elements;
    }


    /**
     * * Este método empuja hacia abajo un elemento concreto del montón desde su posición actual.
     * * También actualiza su posición en el hashmap.
     * 
     * This method pushes down one particular element in the heap from its current position.
     * It also updates its hashmap position.
     *
     * @param Mixed $element     The element to move down in the heap. // *  $elemento   El elemento a mover hacia abajo en el montón.
     * @param Integer $pos       Position where the element is now.    // *  $pos        Posición en la que se encuentra el elemento.
     * @param Integer $priority  The new priority for the element      // *  $priority   La nueva prioridad del elemento
     *
     * @return void
     */
    private function down_heap($element, $pos, $priority): void
    {
        //* Apuntar al primer hijo
        //Point to the first child
        $top      = $pos;
        $tops_son = $top * 2;

        //* Si el primer hijo existe y la prioridad del hermano es mayor, señala al hermano
        //If first son exists and sibling's priority is higher, point to the sibling
        if (($tops_son < $this->num_elements) && ($this->queue[$tops_son+1]['priority'] < $this->queue[$tops_son]['priority'])) {
            $tops_son++;
        }

        //* Mientras el hijo exista y la prioridad sea mayor que el último elemento, ¡cambia!
        //While son exists and priority is higher than last element, change!
        while (($tops_son < $this->num_elements) && ($this->queue[$tops_son]['priority'] < $priority)) {
            $this->queue[$top] = $this->queue[$tops_son];
            $this->hashmap[hash($this->algorithm, $this->queue[$top]['element'])] = $top;

            $top               = $tops_son;
            $tops_son          = $top * 2;

            //* De nuevo, si el primer hijo existe y la prioridad del hermano es mayor, apunta al hermano
            //Again, if first son exists and sibling's priority is higher, point to the sibling
            if (($tops_son < $this->num_elements) && ($this->queue[$tops_son+1]['priority'] < $this->queue[$tops_son]['priority'])) {
                $tops_son++;
            }
        }

        //* Se encuentra el destino final del elemento.
        // Final destination for the element is found.
        $this->queue[$top]['element']   = $element;
        $this->queue[$top]['priority']  = $priority;
        $this->queue[$top]['timestamp'] = time();
        $this->hashmap[hash($this->algorithm, $element)] = $top;

        $this->hashmap[hash($this->algorithm, $this->queue[$pos]['element'])] = $pos;
    }


    /**
     * * Este método eleva un elemento concreto del montón desde su posición actual.
     * * También actualiza su posición en el hashmap.
     * This method lifts up one particular element in the heap from its current position.
     * It also updates its hashmap position.
     *
     * @param Mixed $element     The element to raise up in the heap. //* El elemento a subir en el montón
     * @param Integer $pos       Position where the element is now.   //* Posición en la que se encuentra el elemento ahora.
     * @param Integer $priority  The new priority for the element     //* La nueva prioridad del elemento
     *
     * @return void
     */
    private function up_heap($element, $pos, $priority)
    {
        $next_position    = $pos;        //Pointer to the next free position in the queue. //* //Punta a la siguiente posición libre en la cola.
        $fathers_position = intdiv($next_position, 2);  //Obtain father's position of the new element. //* //Obtener la posición del padre del nuevo elemento. 

        while (($fathers_position > 0) &&
               ($this->queue[$fathers_position]['priority'] > $priority)) {
            $this->queue[$next_position] = $this->queue[$fathers_position];
            $this->hashmap[hash($this->algorithm, $this->queue[$next_position]['element'])] = $next_position;

            $next_position               = $fathers_position;
            $fathers_position            = intdiv($next_position, 2);
        }

        //* Se encuentra el destino final del elemento
        // Final destination for the element is found.
        $this->queue[$next_position]['element']   = $element;
        $this->queue[$next_position]['priority']  = $priority;
        $this->queue[$next_position]['timestamp'] = time();
        $this->hashmap[hash($this->algorithm, $element)] = $next_position;

        //* Restablece la posición del elemento anterior en el hashmap.
        // Reset previous element position in the hashmap.
        $this->hashmap[hash($this->algorithm, $this->queue[$pos]['element'])] = $pos;
    }


    /**
     * * Obtener la posición de un elemento en coste O(1). :-)
     * Get the position of one element in cost O(1).  :-)
     *
     * @param Mixed $element
     * @return Integer|boolean
     */
    private function getPosition($element)
    {
        $position = hash($this->algorithm, $element);
        if (isset($this->hashmap[$position])) {
            return $this->hashmap[$position];
        } else {
            return false;
        }
    }


    /**
     * * Sólo una función de ayuda para imprimir la cola
     * Just a help function to print out the queue
     *
     * @return void
     */
    public function print(): void
    {
        for ($i=1; $i<=$this->num_elements; $i++) {
            echo $i." - ".$this->queue[$i]['priority']." - ".$this->queue[$i]['element']."\n";
        }
    }


}
?>